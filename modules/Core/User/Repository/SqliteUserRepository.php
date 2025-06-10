<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Repository;

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
        $this->initializeDatabase();
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT *, password_hash as password FROM users WHERE id = ?');
        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT *, password_hash as password FROM users WHERE email = ?');
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
        // Table already exists with password_hash column
        // We'll work with the existing schema
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
            INSERT INTO users (id, email, name, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userData['id'],
            $userData['email'],
            $userData['name'],
            $userData['password'],
            $userData['role'] ?? 'user',
            $userData['status'] ?? 'active',
            $userData['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    private function updateUser(array $userData): void
    {
        $sql = '
            UPDATE users 
            SET email = ?, name = ?, password = ?, role = ?, status = ?, updated_at = ?
            WHERE id = ?
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userData['email'],
            $userData['name'],
            $userData['password'],
            $userData['role'],
            $userData['status'],
            date('Y-m-d H:i:s'),
            $userData['id'],
        ]);
    }
}
