<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\CQRS\Events;

use HdmBoot\SharedKernel\Events\DomainEvent;

/**
 * Domain Event Interface for CQRS.
 *
 * Represents something that happened in the domain.
 * Events are immutable facts about state changes.
 * This extends the unified DomainEvent interface.
 *
 * @deprecated Use HdmBoot\SharedKernel\Events\DomainEvent instead
 */
interface DomainEventInterface extends DomainEvent
{
    // This interface now extends the unified DomainEvent interface
    // All methods are inherited from the base interface
}
