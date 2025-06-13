<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\Events;

use MvaBootstrap\Modules\Core\Language\Domain\Models\Translation;
use MvaBootstrap\SharedKernel\Events\DomainEvent;

/**
 * Translation Added Event.
 *
 * Fired when a new translation is added.
 */
final readonly class TranslationAddedEvent implements DomainEvent
{
    public function __construct(
        public Translation $translation,
        public ?string $addedBy,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * Create event.
     */
    public static function create(Translation $translation, ?string $addedBy = null): self
    {
        return new self(
            $translation,
            $addedBy,
            new \DateTimeImmutable()
        );
    }

    /**
     * Get event name.
     */
    public function getEventName(): string
    {
        return 'language.translation_added';
    }

    /**
     * Get event data.
     *
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'translation_key' => $this->translation->getKey()->toString(),
            'locale'          => $this->translation->getLocale()->toString(),
            'value'           => $this->translation->getValue(),
            'added_by'        => $this->addedBy,
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
