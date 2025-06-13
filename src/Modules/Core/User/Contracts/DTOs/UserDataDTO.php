<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Contracts\DTOs;

/**
 * User Data DTO - Public User Data Structure.
 *
 * Standardized user data structure for inter-module communication.
 * This DTO ensures consistent user data format across modules.
 */
final readonly class UserDataDTO
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
     * Create from array data.
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
     * Check if user has specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get user display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?: $this->email;
    }
}
