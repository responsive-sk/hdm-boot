<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

/**
 * Abstract System Event.
 *
 * Base implementation for system events providing common functionality
 * for runtime inter-module communication.
 */
abstract class AbstractSystemEvent extends AbstractDomainEvent implements SystemEvent
{
    private readonly int $priority;
    private readonly bool $async;
    private readonly array $context;

    public function __construct(
        int $priority = 0,
        bool $async = false,
        array $context = [],
        int $version = 1,
        ?\DateTimeImmutable $occurredAt = null,
        ?string $eventId = null
    ) {
        parent::__construct($version, $occurredAt, $eventId);
        
        $this->priority = $priority;
        $this->async = $async;
        $this->context = $context;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['priority'] = $this->getPriority();
        $data['async'] = $this->isAsync();
        $data['context'] = $this->getContext();
        
        return $data;
    }

    public function toLogArray(): array
    {
        $data = parent::toLogArray();
        $data['priority'] = $this->getPriority();
        $data['async'] = $this->isAsync();
        $data['context'] = $this->getContext();
        
        return $data;
    }
}
