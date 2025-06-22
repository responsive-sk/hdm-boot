<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * User ID Value Object
 *
 * Represents a unique user identifier using UUID.
 */
final class UserId
{
    private readonly UuidInterface $uuid;

    private function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Generate new random UserId.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create UserId from string.
     */
    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException("Invalid UUID format: {$id}");
        }

        return new self(Uuid::fromString($id));
    }

    /**
     * Create UserId from UUID interface.
     */
    public static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid);
    }

    /**
     * Get string representation.
     */
    public function toString(): string
    {
        return $this->uuid->toString();
    }

    /**
     * Get UUID interface.
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * Check equality with another UserId.
     */
    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
