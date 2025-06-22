<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Domain\Models;

/**
 * User Domain Model.
 *
 * Represents a user entity with business logic and validation.
 * This is a simple domain model that wraps array data with type safety.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $lastLoginAt = null
    ) {
    }

    /**
     * Create User from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            role: (string) ($data['role'] ?? 'user'),
            status: (string) ($data['status'] ?? 'active'),
            createdAt: (string) ($data['created_at'] ?? ''),
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
            lastLoginAt: isset($data['last_login_at']) ? (string) $data['last_login_at'] : null
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'email'         => $this->email,
            'name'          => $this->name,
            'role'          => $this->role,
            'status'        => $this->status,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
            'last_login_at' => $this->lastLoginAt,
        ];
    }

    /**
     * Convert to public array (without sensitive data).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id'            => $this->id,
            'email'         => $this->email,
            'name'          => $this->name,
            'role'          => $this->role,
            'status'        => $this->status,
            'created_at'    => $this->createdAt,
            'last_login_at' => $this->lastLoginAt,
        ];
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is editor.
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user has specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified roles.
     *
     * @param array<string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Get user display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Validate user data.
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->id))) {
            $errors[] = 'User ID is required';
        }

        if (empty(trim($this->email))) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty(trim($this->name))) {
            $errors[] = 'Name is required';
        }

        if (!in_array($this->role, ['admin', 'editor', 'user'], true)) {
            $errors[] = 'Invalid user role';
        }

        if (!in_array($this->status, ['active', 'inactive', 'suspended'], true)) {
            $errors[] = 'Invalid user status';
        }

        return $errors;
    }

    /**
     * Check if user data is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
