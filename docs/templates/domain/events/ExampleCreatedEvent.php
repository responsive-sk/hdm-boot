<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Example\Domain\Events;

use MvaBootstrap\SharedKernel\Events\DomainEventInterface;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * Event emitted when a new Example entity is created.
 *
 * @implements JsonSerializable<string, string|int>
 */
final readonly class ExampleCreatedEvent implements DomainEventInterface, JsonSerializable
{
    public function __construct(
        private int $exampleId,
        private string $name,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function getExampleId(): int
    {
        return $this->exampleId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array{
     *     event_type: string,
     *     example_id: int,
     *     name: string,
     *     occurred_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'event_type' => 'example.created',
            'example_id' => $this->exampleId,
            'name' => $this->name,
            'occurred_at' => $this->occurredAt->format(DateTimeInterface::ATOM)
        ];
    }
}
