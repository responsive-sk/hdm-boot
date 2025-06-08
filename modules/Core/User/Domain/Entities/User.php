<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;

/**
 * User Domain Entity.
 *
 * Rich domain object with business logic for user management.
 */
final class User implements JsonSerializable
{
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        private UserId $id,
        private string $email,
        private string $name,
        private string $passwordHash,
        private string $role = 'user',
        private string $status = 'active',
        private bool $emailVerified = false,
        private ?string $emailVerificationToken = null,
        private ?string $passwordResetToken = null,
        private ?DateTimeImmutable $passwordResetExpires = null,
        private ?DateTimeImmutable $lastLoginAt = null,
        private int $loginCount = 0,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->email = $this->validateEmail($email);
        $this->name = $this->validateName($name);
        $this->validateRole($role);
        $this->validateStatus($status);
        
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    /**
     * Create new user.
     */
    public static function create(
        string $email,
        string $name,
        string $password,
        string $role = 'user'
    ): self {
        return new self(
            id: UserId::generate(),
            email: $email,
            name: $name,
            passwordHash: password_hash($password, PASSWORD_ARGON2ID),
            role: $role
        );
    }

    /**
     * Create user from database data.
     */
    public static function fromDatabase(array $data): self
    {
        return new self(
            id: UserId::fromString($data['id']),
            email: $data['email'],
            name: $data['name'],
            passwordHash: $data['password_hash'],
            role: $data['role'] ?? 'user',
            status: $data['status'] ?? 'active',
            emailVerified: (bool)($data['email_verified'] ?? false),
            emailVerificationToken: $data['email_verification_token'] ?? null,
            passwordResetToken: $data['password_reset_token'] ?? null,
            passwordResetExpires: isset($data['password_reset_expires']) 
                ? new DateTimeImmutable($data['password_reset_expires']) 
                : null,
            lastLoginAt: isset($data['last_login_at']) 
                ? new DateTimeImmutable($data['last_login_at']) 
                : null,
            loginCount: (int)($data['login_count'] ?? 0),
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at'])
        );
    }

    // Getters
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

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpires(): ?DateTimeImmutable
    {
        return $this->passwordResetExpires;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // Business logic methods
    public function updateEmail(string $email): void
    {
        $this->email = $this->validateEmail($email);
        $this->emailVerified = false; // Reset verification when email changes
        $this->touch();
    }

    public function updateName(string $name): void
    {
        $this->name = $this->validateName($name);
        $this->touch();
    }

    public function changePassword(string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        $this->passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $this->passwordResetToken = null;
        $this->passwordResetExpires = null;
        $this->touch();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function changeRole(string $role): void
    {
        $this->validateRole($role);
        $this->role = $role;
        $this->touch();
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->touch();
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->touch();
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
        $this->emailVerificationToken = null;
        $this->touch();
    }

    public function generateEmailVerificationToken(): string
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->touch();
        return $this->emailVerificationToken;
    }

    public function generatePasswordResetToken(): string
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetExpires = new DateTimeImmutable('+1 hour');
        $this->touch();
        return $this->passwordResetToken;
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->loginCount++;
        $this->touch();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified' => $this->emailVerified,
            'last_login_at' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'login_count' => $this->loginCount,
            'is_active' => $this->isActive(),
            'is_admin' => $this->isAdmin(),
            'is_editor' => $this->isEditor(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Private helper methods
    private function validateEmail(string $email): string
    {
        $email = trim($email);
        if (empty($email)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        return strtolower($email);
    }

    private function validateName(string $name): string
    {
        $name = trim($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Name cannot be empty');
        }
        if (strlen($name) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters long');
        }

        return $name;
    }

    private function validateRole(string $role): void
    {
        $allowedRoles = ['user', 'editor', 'admin'];
        if (!in_array($role, $allowedRoles, true)) {
            throw new InvalidArgumentException(
                'Invalid role. Allowed roles: ' . implode(', ', $allowedRoles)
            );
        }
    }

    private function validateStatus(string $status): void
    {
        $allowedStatuses = ['active', 'inactive', 'suspended', 'pending'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException(
                'Invalid status. Allowed statuses: ' . implode(', ', $allowedStatuses)
            );
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
