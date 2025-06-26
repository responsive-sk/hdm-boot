<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Domain\Events;

use HdmBoot\SharedKernel\CQRS\Events\DomainEventInterface;

/**
 * User Was Updated Event.
 *
 * Domain event fired when user information is updated.
 */
final readonly class UserWasUpdated implements DomainEventInterface
{
    public function __construct(
        public string $eventId,
        public string $userId,
        public array $previousData,
        public array $newData,
        public array $changedFields,
        public string $updatedBy,
        public string $clientIp,
        public \DateTimeImmutable $occurredAt,
        public int $version = 1
    ) {
    }

    /**
     * Create event from update data.
     *
     * @param array<string, mixed> $previousData
     * @param array<string, mixed> $newData
     * @param array<string, mixed> $metadata
     */
    public static function fromUpdateData(
        string $userId,
        array $previousData,
        array $newData,
        array $metadata = []
    ): self {
        $changedFields = [];
        foreach ($newData as $field => $value) {
            if (!isset($previousData[$field]) || $previousData[$field] !== $value) {
                $changedFields[] = $field;
            }
        }

        return new self(
            eventId: uniqid('user_updated_', true),
            userId: $userId,
            previousData: $previousData,
            newData: $newData,
            changedFields: $changedFields,
            updatedBy: (string) ($metadata['updated_by'] ?? 'system'),
            clientIp: (string) ($metadata['client_ip'] ?? '127.0.0.1'),
            occurredAt: new \DateTimeImmutable(),
            version: 1
        );
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return 'user_was_updated';
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
            'event_id'       => $this->eventId,
            'event_name'     => $this->getEventName(),
            'user_id'        => $this->userId,
            'previous_data'  => $this->previousData,
            'new_data'       => $this->newData,
            'changed_fields' => $this->changedFields,
            'updated_by'     => $this->updatedBy,
            'client_ip'      => $this->clientIp,
            'occurred_at'    => $this->occurredAt->format('Y-m-d H:i:s'),
            'version'        => $this->version,
        ];
    }

    public function toLogArray(): array
    {
        return [
            'event_id'       => $this->eventId,
            'event_name'     => $this->getEventName(),
            'user_id'        => $this->userId,
            'changed_fields' => $this->changedFields,
            'updated_by'     => $this->updatedBy,
            'occurred_at'    => $this->occurredAt->format('Y-m-d H:i:s'),
            'version'        => $this->version,
        ];
    }
}
