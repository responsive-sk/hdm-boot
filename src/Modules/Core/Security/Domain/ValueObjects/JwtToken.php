<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Domain\ValueObjects;

/**
 * JWT Token Value Object
 *
 * Represents a JSON Web Token with its payload and metadata.
 */
final class JwtToken
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $token,
        private readonly array $payload,
        private readonly \DateTimeImmutable $expiresAt
    ) {
    }

    /**
     * Create JwtToken from token string.
     */
    public static function fromString(string $tokenString): self
    {
        // For now, create a basic token object
        // In production, you would decode and validate the JWT
        return new self(
            $tokenString,
            [], // Empty payload for now
            new \DateTimeImmutable('+1 hour') // Default expiration
        );
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
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
     * Get user email from payload.
     */
    public function getUserEmail(): ?string
    {
        $email = $this->payload['email'] ?? null;
        return is_string($email) ? $email : null;
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
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    /**
     * Get time to expiration in seconds.
     */
    public function getTimeToExpiration(): int
    {
        $now = new \DateTimeImmutable();
        if ($this->expiresAt <= $now) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Check if payload has specific key.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->payload);
    }

    /**
     * Get value from payload.
     */
    public function get(string $key): mixed
    {
        return $this->payload[$key] ?? null;
    }

    /**
     * Array access for payload.
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }
        return array_key_exists($offset, $this->payload);
    }

    /**
     * Array access for payload.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->payload[$offset] ?? null;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'payload' => $this->payload,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'expires_in' => $this->getTimeToExpiration(),
            'is_expired' => $this->isExpired(),
        ];
    }

    /**
     * String representation returns the token.
     */
    public function __toString(): string
    {
        return $this->token;
    }
}
