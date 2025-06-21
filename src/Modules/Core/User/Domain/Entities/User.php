<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Domain\Entities;

use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;

/**
 * User Entity
 *
 * Represents a user in the system with all their properties and behaviors.
 */
class User
{
    public function __construct(
        private readonly UserId $id,
        private string $email,
        private string $name,
        private string $role,
        private string $status,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateEmail(string $email): self
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function updateName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function updateRole(string $role): self
    {
        $this->role = $role;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function updateStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            UserId::fromString((string) $data['id']),
            (string) $data['email'],
            (string) $data['name'],
            (string) $data['role'],
            (string) $data['status'],
            new \DateTimeImmutable((string) $data['created_at']),
            isset($data['updated_at']) ? new \DateTimeImmutable((string) $data['updated_at']) : null
        );
    }

    /**
     * Check if user's email is verified.
     */
    public function isEmailVerified(): bool
    {
        // For now, return true. In production, you would check email_verified_at field
        return true;
    }
}
