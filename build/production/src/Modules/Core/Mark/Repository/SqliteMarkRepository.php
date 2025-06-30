<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Mark\Repository;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * SQLite Mark Repository.
 *
 * Implements mark user data access using SQLite (mark.db).
 * Uses mark.db database exclusively.
 */
final class SqliteMarkRepository implements MarkRepositoryInterface
{
    public function __construct(
        private readonly PDO $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $this->logger->debug('🔴 MARK REPO: Finding user by email', ['email' => $email]);

        try {
            $stmt = $this->connection->prepare('
                SELECT id, username, email, password_hash, role, status, last_login_at, login_count, created_at, updated_at
                FROM mark_users 
                WHERE email = ? AND status = "active"
            ');

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user === false) {
                $this->logger->debug('🔴 MARK REPO: User not found', ['email' => $email]);

                return null;
            }

            if (!is_array($user)) {
                $this->logger->error('🔴 MARK REPO: Invalid user data type', ['email' => $email]);

                return null;
            }

            $this->logger->debug('🔴 MARK REPO: User found', [
                'email'   => $email,
                'user_id' => $user['id'] ?? 'unknown',
                'role'    => $user['role'] ?? 'unknown',
            ]);

            // Ensure proper array structure for return type
            $typedUser = [];
            foreach ($user as $key => $value) {
                if (is_string($key)) {
                    $typedUser[$key] = $value;
                }
            }

            return $typedUser;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error finding user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        $this->logger->debug('🔴 MARK REPO: Finding user by ID', ['user_id' => $id]);

        try {
            $stmt = $this->connection->prepare('
                SELECT id, username, email, password_hash, role, status, last_login_at, login_count, created_at, updated_at
                FROM mark_users 
                WHERE id = ?
            ');

            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user === false) {
                return null;
            }

            if (!is_array($user)) {
                $this->logger->error('🔴 MARK REPO: Invalid user data type', ['user_id' => $id]);

                return null;
            }

            // Ensure proper array structure for return type
            $typedUser = [];
            foreach ($user as $key => $value) {
                if (is_string($key)) {
                    $typedUser[$key] = $value;
                }
            }

            return $typedUser;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error finding user by ID', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updateLastLogin(string $id): bool
    {
        try {
            $stmt = $this->connection->prepare('
                UPDATE mark_users 
                SET last_login_at = datetime("now"), 
                    login_count = login_count + 1,
                    updated_at = datetime("now")
                WHERE id = ?
            ');

            $result = $stmt->execute([$id]);

            $this->logger->debug('🔴 MARK REPO: Updated last login', [
                'user_id' => $id,
                'success' => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error updating last login', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->connection->query('
                SELECT id, username, email, role, status, last_login_at, login_count, created_at, updated_at
                FROM mark_users
                ORDER BY created_at DESC
            ');

            if ($stmt === false) {
                $this->logger->error('🔴 MARK REPO: Failed to prepare statement for findAll');

                return [];
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ensure proper array structure
            $typedResult = [];
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (is_array($row)) {
                        // Ensure string keys
                        $typedRow = [];
                        foreach ($row as $key => $value) {
                            if (is_string($key)) {
                                $typedRow[$key] = $value;
                            }
                        }
                        $typedResult[] = $typedRow;
                    }
                }
            }

            return $typedResult;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error finding all users', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    public function create(array $userData): string
    {
        try {
            $id = 'mark-' . uniqid();
            $now = date('Y-m-d H:i:s');

            $stmt = $this->connection->prepare('
                INSERT INTO mark_users (id, username, email, password_hash, role, status, last_login_at, login_count, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $id,
                $userData['username'] ?? '',
                $userData['email'],
                $userData['password_hash'],
                $userData['role'] ?? 'mark_admin',
                $userData['status'] ?? 'active',
                null,
                0,
                $now,
                $now,
            ]);

            $this->logger->info('🔴 MARK REPO: Created new mark user', [
                'user_id' => $id,
                'email'   => $userData['email'],
            ]);

            return $id;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error creating user', [
                'email' => $userData['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $userData
     */
    public function update(string $id, array $userData): bool
    {
        try {
            $fields = [];
            $values = [];

            foreach ($userData as $field => $value) {
                if ($field !== 'id') {
                    $fields[] = "{$field} = ?";
                    $values[] = $value;
                }
            }

            $fields[] = 'updated_at = ?';
            $values[] = date('Y-m-d H:i:s');
            $values[] = $id;

            $sql = 'UPDATE mark_users SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $this->connection->prepare($sql);

            $result = $stmt->execute($values);

            $this->logger->info('🔴 MARK REPO: Updated mark user', [
                'user_id' => $id,
                'success' => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error updating user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM mark_users WHERE id = ?');
            $result = $stmt->execute([$id]);

            $this->logger->info('🔴 MARK REPO: Deleted mark user', [
                'user_id' => $id,
                'success' => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK REPO: Error deleting user', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }
}
