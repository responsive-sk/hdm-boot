<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * User ID Value Object.
 *
 * Type-safe UUID identifier for User entities.
 * Uses UUID v4 for security - prevents user enumeration attacks.
 */
final readonly class UserId
{
    public function __construct(private string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('User ID cannot be empty');
        }

        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('User ID must be a valid UUID');
        }
    }

    /**
     * Generate new UserId using UUID v4.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * Create UserId from string.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Get the string value of the ID.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Convert to string.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another UserId.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
