<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Domain\Events;

use HdmBoot\Modules\Core\Language\Domain\ValueObjects\Locale;
use HdmBoot\SharedKernel\Events\DomainEvent;

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

    /**
     * Get event identifier for tracking.
     */
    public function getEventId(): string
    {
        return 'locale_changed_' . ($this->userId ?? 'anonymous') . '_' . $this->occurredAt->getTimestamp();
    }

    /**
     * Get event version for evolution.
     */
    public function getVersion(): int
    {
        return 1;
    }

    /**
     * Get event payload for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'event_name' => $this->getEventName(),
            'version' => $this->getVersion(),
            'user_id' => $this->userId,
            'previous_locale' => $this->previousLocale->toString(),
            'new_locale' => $this->newLocale->toString(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get event data for logging.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'user_id' => $this->userId,
            'previous_locale' => $this->previousLocale->toString(),
            'new_locale' => $this->newLocale->toString(),
        ];
    }
}
