<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Storage\Models;

/**
 * User Model.
 *
 * Represents users stored in SQLite database.
 * Part of hybrid storage approach - database for relational data.
 */
class User extends DatabaseModel
{
    /**
     * Storage driver name.
     */
    protected static string $driver = 'sqlite';

    /**
     * Table name.
     */
    protected static string $table = 'users';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if primary key is auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * Define the schema for users.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'id' => 'integer|primary|auto_increment',
            'username' => 'string|required|unique',
            'email' => 'string|required|unique',
            'password_hash' => 'string|required',
            'first_name' => 'string|nullable',
            'last_name' => 'string|nullable',
            'role' => 'string|default:user',
            'status' => 'string|default:active', // active, inactive, banned
            'email_verified' => 'boolean|default:false',
            'email_verified_at' => 'datetime|nullable',
            'last_login_at' => 'datetime|nullable',
            'login_count' => 'integer|default:0',
            'preferences' => 'json|nullable',
            'created_at' => 'datetime|auto',
            'updated_at' => 'datetime|auto',
        ];
    }

    /**
     * Get active users.
     *
     * @return array<int, static>
     */
    public static function active(): array
    {
        return array_filter(static::all(), function (User $user) {
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
        return array_filter(static::all(), function (User $user) use ($role) {
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
     * Get full name.
     */
    public function getFullName(): string
    {
        $firstNameRaw = $this->getAttribute('first_name');
        $lastNameRaw = $this->getAttribute('last_name');
        $usernameRaw = $this->getAttribute('username');

        $firstName = is_string($firstNameRaw) ? $firstNameRaw : '';
        $lastName = is_string($lastNameRaw) ? $lastNameRaw : '';
        $username = is_string($usernameRaw) ? $usernameRaw : 'Unknown';

        return trim($firstName . ' ' . $lastName) ?: $username;
    }

    /**
     * Check if user has role.
     */
    public function hasRole(string $role): bool
    {
        return $this->getAttribute('role') === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->getAttribute('status') === 'active';
    }

    /**
     * Check if email is verified.
     */
    public function isEmailVerified(): bool
    {
        return (bool) $this->getAttribute('email_verified');
    }

    /**
     * Mark email as verified.
     */
    public function markEmailAsVerified(): self
    {
        $this->setAttribute('email_verified', true);
        $this->setAttribute('email_verified_at', date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * Record login.
     */
    public function recordLogin(): self
    {
        $this->setAttribute('last_login_at', date('Y-m-d H:i:s'));

        // Safe increment of login count
        $currentCount = $this->getAttribute('login_count');
        $loginCount = is_numeric($currentCount) ? (int) $currentCount : 0;
        $this->setAttribute('login_count', $loginCount + 1);

        return $this;
    }

    /**
     * Get user preferences.
     *
     * @return array<string, mixed>
     */
    public function getPreferences(): array
    {
        $preferences = $this->getAttribute('preferences');

        if (is_string($preferences)) {
            $decoded = json_decode($preferences, true);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $typedDecoded */
                $typedDecoded = $decoded;
                return $typedDecoded;
            }
            return [];
        }

        if (is_array($preferences)) {
            /** @var array<string, mixed> $typedPreferences */
            $typedPreferences = $preferences;
            return $typedPreferences;
        }

        return [];
    }

    /**
     * Set user preferences.
     *
     * @param array<string, mixed> $preferences
     */
    public function setPreferences(array $preferences): self
    {
        $this->setAttribute('preferences', json_encode($preferences));
        return $this;
    }

    /**
     * Get specific preference.
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = $this->getPreferences();
        return $preferences[$key] ?? $default;
    }

    /**
     * Set specific preference.
     */
    public function setPreference(string $key, mixed $value): self
    {
        $preferences = $this->getPreferences();
        $preferences[$key] = $value;
        return $this->setPreferences($preferences);
    }

    /**
     * Get avatar URL or initials.
     */
    public function getAvatar(): string
    {
        // For now, return initials-based avatar
        $name = $this->getFullName();
        $initials = '';

        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }

        return $initials ?: 'U';
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
        $data['full_name'] = $this->getFullName();
        $data['avatar'] = $this->getAvatar();
        $data['is_admin'] = $this->isAdmin();
        $data['is_active'] = $this->isActive();
        $data['is_email_verified'] = $this->isEmailVerified();

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
