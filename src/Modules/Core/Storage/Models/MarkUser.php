<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

/**
 * Mark User Model.
 *
 * Represents Mark admin users stored in mark.db database.
 * Separate from app users for security isolation.
 */
class MarkUser extends DatabaseModel
{
    /**
     * Storage driver name.
     */
    protected static string $driver = 'sqlite';

    /**
     * Database name.
     */
    protected static string $database = 'mark';

    /**
     * Table name.
     */
    protected static string $table = 'mark_users';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if primary key is auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * Define the schema for mark users.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'id'            => 'integer|primary|auto_increment',
            'username'      => 'string|required|unique',
            'email'         => 'string|required|unique',
            'password_hash' => 'string|required',
            'role'          => 'string|default:admin', // admin, super_admin
            'status'        => 'string|default:active', // active, inactive
            'last_login_at' => 'datetime|nullable',
            'created_at'    => 'datetime|auto',
            'updated_at'    => 'datetime|auto',
        ];
    }

    /**
     * Get active mark users.
     *
     * @return array<int, static>
     */
    public static function active(): array
    {
        return array_filter(static::all(), function (MarkUser $user) {
            return $user->getAttribute('status') === 'active';
        });
    }

    /**
     * Get users by role.
     *
     * @return array<int, static>
     */
    public static function byRole(string $role): array
    {
        return array_filter(static::all(), function (MarkUser $user) use ($role) {
            return $user->getAttribute('role') === $role;
        });
    }

    /**
     * Find user by username.
     */
    public static function findByUsername(string $username): ?static
    {
        $users = static::all();

        foreach ($users as $user) {
            if ($user->getAttribute('username') === $username) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Find user by email.
     */
    public static function findByEmail(string $email): ?static
    {
        $users = static::all();

        foreach ($users as $user) {
            if ($user->getAttribute('email') === $email) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Verify password.
     */
    public function verifyPassword(string $password): bool
    {
        $hash = $this->getAttribute('password_hash');

        return is_string($hash) && password_verify($password, $hash);
    }

    /**
     * Set password (automatically hashes).
     */
    public function setPassword(string $password): self
    {
        $this->setAttribute('password_hash', password_hash($password, PASSWORD_DEFAULT));

        return $this;
    }

    /**
     * Check if user has role.
     */
    public function hasRole(string $role): bool
    {
        return $this->getAttribute('role') === $role;
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->getAttribute('status') === 'active';
    }

    /**
     * Record login.
     */
    public function recordLogin(): self
    {
        $this->setAttribute('last_login_at', date('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * Log admin action.
     *
     * @param array<string, mixed>|null $details
     */
    public function logAction(string $action, ?string $resourceType = null, ?string $resourceId = null, ?array $details = null): void
    {
        MarkAuditLog::create([
            'user_id'       => $this->getKey(),
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'details'       => $details ? json_encode($details) : null,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Get user's audit logs.
     *
     * @return array<int, MarkAuditLog>
     */
    public function getAuditLogs(): array
    {
        return array_filter(MarkAuditLog::all(), function (MarkAuditLog $log) {
            return $log->getAttribute('user_id') == $this->getKey();
        });
    }

    /**
     * Convert to safe array (without sensitive data).
     *
     * @return array<string, mixed>
     */
    public function toSafeArray(): array
    {
        $data = $this->toArray();

        // Remove sensitive fields
        unset($data['password_hash']);

        // Add computed fields
        $data['is_super_admin'] = $this->isSuperAdmin();
        $data['is_active'] = $this->isActive();

        return $data;
    }

    /**
     * Save with automatic fields.
     */
    public function save(): bool
    {
        // Set created_at if new record
        if (!$this->exists() && empty($this->getAttribute('created_at'))) {
            $this->setAttribute('created_at', date('Y-m-d H:i:s'));
        }

        // Always update updated_at
        $this->setAttribute('updated_at', date('Y-m-d H:i:s'));

        return parent::save();
    }
}
