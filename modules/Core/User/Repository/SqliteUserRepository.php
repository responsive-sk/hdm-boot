<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Repository;

use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;
use PDO;
use PDOException;
use RuntimeException;

/**
 * SQLite User Repository.
 *
 * Implements UserRepositoryInterface using SQLite database.
 */
final class SqliteUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->initializeDatabase();
    }

    public function findById(UserId $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id->toString()]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($data)) {
            return null;
        }

        return User::fromDatabase($data);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([strtolower($email)]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($data)) {
            return null;
        }

        return User::fromDatabase($data);
    }

    public function findByEmailVerificationToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email_verification_token = ?');
        $stmt->execute([$token]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($data)) {
            return null;
        }

        return User::fromDatabase($data);
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > datetime("now")
        ');
        $stmt->execute([$token]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($data)) {
            return null;
        }

        return User::fromDatabase($data);
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM users';
        $params = [];
        $conditions = [];

        // Apply filters
        if (!empty($filters['role'])) {
            $conditions[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['email_verified'])) {
            $conditions[] = 'email_verified = ?';
            $params[] = $filters['email_verified'] ? 1 : 0;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $users = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($data)) {
                $users[] = User::fromDatabase($data);
            }
        }

        return $users;
    }

    public function findByRole(string $role): array
    {
        return $this->findAll(['role' => $role]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findAll(['status' => $status]);
    }

    public function save(User $user): void
    {
        try {
            $this->pdo->beginTransaction();

            if ($this->userExists($user->getId())) {
                $this->updateUser($user);
            } else {
                $this->insertUser($user);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(UserId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id->toString()]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([strtolower($email)]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        if ($stmt === false) {
            throw new RuntimeException('Failed to execute count query');
        }

        $result = $stmt->fetchColumn();
        return $result !== false ? (int) $result : 0;
    }

    public function countByStatus(): array
    {
        $stmt = $this->pdo->query('
            SELECT status, COUNT(*) as count 
            FROM users 
            GROUP BY status
        ');

        $counts = [];
        if ($stmt !== false) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (is_array($row) && isset($row['status'], $row['count'])) {
                    $counts[(string) $row['status']] = (int) $row['count'];
                }
            }
        }

        return $counts;
    }

    public function countByRole(): array
    {
        $stmt = $this->pdo->query('
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ');

        $counts = [];
        if ($stmt !== false) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (is_array($row) && isset($row['role'], $row['count'])) {
                    $counts[(string) $row['role']] = (int) $row['count'];
                }
            }
        }

        return $counts;
    }

    public function getStatistics(): array
    {
        return [
            'total_users'    => $this->count(),
            'by_status'      => $this->countByStatus(),
            'by_role'        => $this->countByRole(),
            'email_verified' => $this->countEmailVerified(),
            'recent_logins'  => $this->countRecentLogins(),
        ];
    }

    public function findWithPagination(
        int $page = 1,
        int $limit = 20,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT * FROM users';
        $countSql = 'SELECT COUNT(*) FROM users';
        $params = [];
        $conditions = [];

        // Apply filters
        if (!empty($filters['role'])) {
            $conditions[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($conditions)) {
            $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            $sql .= $whereClause;
            $countSql .= $whereClause;
        }

        // Get total count
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Get paginated results
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $users = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($data)) {
                $users[] = User::fromDatabase($data);
            }
        }

        return [
            'users'       => $users,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ];
    }

    private function userExists(UserId $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $stmt->execute([$id->toString()]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function insertUser(User $user): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (
                id, email, name, password_hash, role, status, email_verified,
                email_verification_token, password_reset_token, password_reset_expires,
                last_login_at, login_count, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $user->getId()->toString(),
            $user->getEmail(),
            $user->getName(),
            $user->getPasswordHash(),
            $user->getRole(),
            $user->getStatus(),
            $user->isEmailVerified() ? 1 : 0,
            $user->getEmailVerificationToken(),
            $user->getPasswordResetToken(),
            $user->getPasswordResetExpires()?->format('Y-m-d H:i:s'),
            $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            $user->getLoginCount(),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    private function updateUser(User $user): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE users SET
                email = ?, name = ?, password_hash = ?, role = ?, status = ?,
                email_verified = ?, email_verification_token = ?, password_reset_token = ?,
                password_reset_expires = ?, last_login_at = ?, login_count = ?, updated_at = ?
            WHERE id = ?
        ');

        $stmt->execute([
            $user->getEmail(),
            $user->getName(),
            $user->getPasswordHash(),
            $user->getRole(),
            $user->getStatus(),
            $user->isEmailVerified() ? 1 : 0,
            $user->getEmailVerificationToken(),
            $user->getPasswordResetToken(),
            $user->getPasswordResetExpires()?->format('Y-m-d H:i:s'),
            $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            $user->getLoginCount(),
            $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            $user->getId()->toString(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function countEmailVerified(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) as unverified
            FROM users
        ');

        $result = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

        if (!is_array($result)) {
            return ['verified' => 0, 'unverified' => 0];
        }

        return [
            'verified'   => isset($result['verified']) ? (int) $result['verified'] : 0,
            'unverified' => isset($result['unverified']) ? (int) $result['unverified'] : 0,
        ];
    }

    private function countRecentLogins(): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM users 
            WHERE last_login_at > datetime("now", "-7 days")
        ');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function initializeDatabase(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                email TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT "user" CHECK (role IN ("user", "editor", "admin")),
                status TEXT NOT NULL DEFAULT "active" CHECK (status IN ("active", "inactive", "suspended", "pending")),
                email_verified BOOLEAN NOT NULL DEFAULT 0,
                email_verification_token TEXT NULL,
                password_reset_token TEXT NULL,
                password_reset_expires TEXT NULL,
                last_login_at TEXT NULL,
                login_count INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL ,
                updated_at TEXT NOT NULL 
            )
        ');

        // Create indexes for better performance
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_email_verification_token ON users(email_verification_token)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_password_reset_token ON users(password_reset_token)');
    }
}
