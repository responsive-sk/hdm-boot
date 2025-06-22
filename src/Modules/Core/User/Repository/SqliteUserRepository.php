<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Repository;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Simplified SQLite User Repository.
 *
 * Handles user data persistence without complex domain entities.
 */
final class SqliteUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
        // Ensure users database is using WAL mode for better concurrency
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->initializeDatabase();
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, name, role, status FROM users WHERE id = ?');
        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function save(array $userData): array
    {
        try {
            $this->pdo->beginTransaction();

            if (isset($userData['id']) && $this->userExists($userData['id'])) {
                // Update existing user
                $this->updateUser($userData);
            } else {
                // Create new user
                $userData['id'] = $this->generateId();
                $this->insertUser($userData);
            }

            $this->pdo->commit();

            return $this->findById($userData['id']);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(string $id, array $data): array
    {
        if (!$this->userExists($id)) {
            throw new RuntimeException('User not found');
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM users';
        $params = [];

        if (!empty($filters)) {
            $conditions = [];

            if (isset($filters['role'])) {
                $conditions[] = 'role = ?';
                $params[] = $filters['role'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = ?';
                $params[] = $filters['status'];
            }

            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatistics(): array
    {
        $stats = [];

        // Total users
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $stats['total'] = (int) $stmt->fetchColumn();

        // Active users
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE status = ?');
        $stmt->execute(['active']);
        $stats['active'] = (int) $stmt->fetchColumn();

        // Users by role
        $stmt = $this->pdo->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
        $stats['by_role'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $stats;
    }

    private function initializeDatabase(): void
    {
        try {
            // Create users table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id TEXT PRIMARY KEY,
                    email TEXT UNIQUE NOT NULL,
                    name TEXT NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'user',
                    status TEXT NOT NULL DEFAULT 'active',
                    email_verified INTEGER NOT NULL DEFAULT 0,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");

            // Create FTS5 virtual table for full-text search
            $this->pdo->exec("
                CREATE VIRTUAL TABLE IF NOT EXISTS users_fts USING fts5(
                    id,
                    email,
                    name,
                    content='users',
                    content_rowid='rowid'
                )
            ");

            // Create trigger to keep FTS index up to date on INSERT
            $this->pdo->exec("
                CREATE TRIGGER IF NOT EXISTS users_ai AFTER INSERT ON users BEGIN
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ");

            // Create trigger for UPDATE
            $this->pdo->exec("
                CREATE TRIGGER IF NOT EXISTS users_au AFTER UPDATE ON users BEGIN
                    DELETE FROM users_fts WHERE rowid = old.rowid;
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ");

            // Create trigger for DELETE
            $this->pdo->exec("
                CREATE TRIGGER IF NOT EXISTS users_ad AFTER DELETE ON users BEGIN
                    DELETE FROM users_fts WHERE rowid = old.rowid;
                END
            ");

            // Create test user if no users exist
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
            $userCount = (int) $stmt->fetchColumn();

            if ($userCount === 0) {
                $this->pdo->exec("
                    INSERT INTO users (
                        id, email, name, password_hash, role, status,
                        email_verified, created_at, updated_at
                    ) VALUES (
                        'test-user-123',
                        'test@example.com',
                        'Test User',
                        '" . password_hash('password123', PASSWORD_DEFAULT) . "',
                        'user',
                        'active',
                        1,
                        datetime('now', 'localtime'),
                        datetime('now', 'localtime')
                    )
                ");
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to initialize database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search users using full-text search
     *
     * @param string $query Search query
     * @param array<string, mixed> $filters Additional filters (role, status, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, array $filters = []): array
    {
        try {
            $sql = "
                WITH RECURSIVE
                search_results AS (
                    SELECT users.*, rank
                    FROM users_fts
                    JOIN users ON users.rowid = users_fts.rowid
                    WHERE users_fts MATCH :query
                    ORDER BY rank
                )
            ";

            $params = [':query' => $query];
            $conditions = [];

            // Apply additional filters
            if (!empty($filters)) {
                if (isset($filters['role'])) {
                    $conditions[] = 'role = :role';
                    $params[':role'] = $filters['role'];
                }
                if (isset($filters['status'])) {
                    $conditions[] = 'status = :status';
                    $params[':status'] = $filters['status'];
                }
            }

            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to search users: ' . $e->getMessage(), 0, $e);
        }
    }

    private function userExists(string $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function generateId(): string
    {
        return uniqid('user_', true);
    }

    private function insertUser(array $userData): void
    {
        $sql = '
            INSERT INTO users (id, email, name, password_hash, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userData['id'],
            $userData['email'],
            $userData['name'],
            $userData['password_hash'] ?? $userData['password'] ?? '',
            $userData['role'] ?? 'user',
            $userData['status'] ?? 'active',
            $userData['created_at'] ?? date('Y-m-d H:i:s'),
            $userData['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    private function updateUser(array $userData): void
    {
        $sql = '
            UPDATE users
            SET email = ?, name = ?, password_hash = ?, role = ?, status = ?, updated_at = ?
            WHERE id = ?
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userData['email'],
            $userData['name'],
            $userData['password_hash'] ?? $userData['password'] ?? '',
            $userData['role'],
            $userData['status'],
            date('Y-m-d H:i:s'),
            $userData['id'],
        ]);
    }
}
