<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * JWT Token Value Object.
 *
 * Represents a JWT token with validation and expiration handling.
 */
final readonly class JwtToken
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $token,
        private DateTimeImmutable $expiresAt,
        private array $payload = []
    ) {
        if (empty($token)) {
            throw new InvalidArgumentException('JWT token cannot be empty');
        }

        if ($this->expiresAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('JWT token cannot be expired');
        }
    }

    /**
     * Create JWT token from string.
     * @param array<string, mixed> $payload
     */
    public static function fromString(string $token, DateTimeImmutable $expiresAt, array $payload = []): self
    {
        return new self($token, $expiresAt, $payload);
    }

    /**
     * Get token string.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get expiration time.
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Get token payload.
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }

    /**
     * Get time until expiration in seconds.
     */
    public function getTimeToExpiration(): int
    {
        $now = new DateTimeImmutable();
        if ($this->expiresAt <= $now) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Get user ID from payload.
     */
    public function getUserId(): ?string
    {
        $userId = $this->payload['user_id'] ?? null;
        return is_string($userId) ? $userId : null;
    }

    /**
     * Get user role from payload.
     */
    public function getUserRole(): ?string
    {
        $role = $this->payload['role'] ?? null;
        return is_string($role) ? $role : null;
    }

    /**
     * Get user email from payload.
     */
    public function getUserEmail(): ?string
    {
        $email = $this->payload['email'] ?? null;
        return is_string($email) ? $email : null;
    }

    /**
     * Check if token has specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Check if token is for admin user.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if token is for editor user.
     */
    public function isEditor(): bool
    {
        return $this->hasRole('editor');
    }

    /**
     * Convert to string representation.
     */
    public function toString(): string
    {
        return $this->token;
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->token;
    }

    /**
     * Convert to array for JSON serialization.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token'      => $this->token,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'expires_in' => $this->getTimeToExpiration(),
            'payload'    => $this->payload,
        ];
    }
}
