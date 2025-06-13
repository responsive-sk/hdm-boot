<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\Events;

use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\SharedKernel\Events\DomainEvent;

/**
 * Locale Changed Event.
 *
 * Fired when user's locale is changed.
 */
final readonly class LocaleChangedEvent implements DomainEvent
{
    public function __construct(
        public ?string $userId,
        public Locale $previousLocale,
        public Locale $newLocale,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * Create event.
     */
    public static function create(
        ?string $userId,
        Locale $previousLocale,
        Locale $newLocale
    ): self {
        return new self(
            $userId,
            $previousLocale,
            $newLocale,
            new \DateTimeImmutable()
        );
    }

    /**
     * Get event name.
     */
    public function getEventName(): string
    {
        return 'language.locale_changed';
    }

    /**
     * Get event data.
     *
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'user_id'         => $this->userId,
            'previous_locale' => $this->previousLocale->toString(),
            'new_locale'      => $this->newLocale->toString(),
            'occurred_at'     => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get occurred at timestamp.
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
