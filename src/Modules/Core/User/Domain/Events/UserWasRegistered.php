<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Domain\Events;

use HdmBoot\SharedKernel\CQRS\Events\DomainEventInterface;

/**
 * User Was Registered Event.
 *
 * Domain event fired when a new user is registered in the system.
 */
final readonly class UserWasRegistered implements DomainEventInterface
{
    public function __construct(
        public string $eventId,
        public string $userId,
        public string $email,
        public string $name,
        public string $role,
        public string $clientIp,
        public ?string $userAgent,
        public \DateTimeImmutable $occurredAt,
        public int $version = 1
    ) {
    }

    /**
     * Create event from user data.
     *
     * @param array<string, mixed> $userData
     * @param array<string, mixed> $metadata
     */
    public static function fromUserData(array $userData, array $metadata = []): self
    {
        return new self(
            eventId: uniqid('user_registered_', true),
            userId: (string) ($userData['id'] ?? ''),
            email: (string) ($userData['email'] ?? ''),
            name: (string) ($userData['name'] ?? ''),
            role: (string) ($userData['role'] ?? 'user'),
            clientIp: (string) ($metadata['client_ip'] ?? '127.0.0.1'),
            userAgent: isset($metadata['user_agent']) ? (string) $metadata['user_agent'] : null,
            occurredAt: new \DateTimeImmutable(),
            version: 1
        );
    }

    /**
     * Create event from stored data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: (string) ($data['event_id'] ?? ''),
            userId: (string) ($data['user_id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            role: (string) ($data['role'] ?? 'user'),
            clientIp: (string) ($data['client_ip'] ?? '127.0.0.1'),
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null,
            occurredAt: new \DateTimeImmutable($data['occurred_at'] ?? 'now'),
            version: (int) ($data['version'] ?? 1)
        );
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return 'user_was_registered';
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'event_id'    => $this->eventId,
            'event_name'  => $this->getEventName(),
            'user_id'     => $this->userId,
            'email'       => $this->email,
            'name'        => $this->name,
            'role'        => $this->role,
            'client_ip'   => $this->clientIp,
            'user_agent'  => $this->userAgent,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'version'     => $this->version,
        ];
    }

    public function toLogArray(): array
    {
        return [
            'event_id'    => $this->eventId,
            'event_name'  => $this->getEventName(),
            'user_id'     => $this->userId,
            'email'       => $this->email,
            'role'        => $this->role,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'version'     => $this->version,
        ];
    }
}
