<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\CQRS\Events;

use MvaBootstrap\SharedKernel\Events\DomainEvent;

/**
 * Domain Event Interface for CQRS.
 *
 * Represents something that happened in the domain.
 * Events are immutable facts about state changes.
 * This extends the unified DomainEvent interface.
 *
 * @deprecated Use MvaBootstrap\SharedKernel\Events\DomainEvent instead
 */
interface DomainEventInterface extends DomainEvent
{
    // This interface now extends the unified DomainEvent interface
    // All methods are inherited from the base interface
}
