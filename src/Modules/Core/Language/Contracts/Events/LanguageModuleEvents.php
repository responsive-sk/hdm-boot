<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Contracts\Events;

/**
 * Language Module Events - Public Event Names.
 *
 * Defines the event names that the Language module publishes
 * for other modules to subscribe to.
 */
final class LanguageModuleEvents
{
    /**
     * Fired when locale is changed.
     */
    public const LOCALE_CHANGED = 'language.locale_changed';

    /**
     * Fired when translation is requested but not found.
     */
    public const TRANSLATION_MISSING = 'language.translation_missing';

    /**
     * Fired when new translation is added.
     */
    public const TRANSLATION_ADDED = 'language.translation_added';

    /**
     * Fired when translation is updated.
     */
    public const TRANSLATION_UPDATED = 'language.translation_updated';

    /**
     * Fired when locale is detected from user preferences.
     */
    public const LOCALE_DETECTED = 'language.locale_detected';

    /**
     * Get all available event names.
     *
     * @return array<string>
     */
    public static function getAllEvents(): array
    {
        return [
            self::LOCALE_CHANGED,
            self::TRANSLATION_MISSING,
            self::TRANSLATION_ADDED,
            self::TRANSLATION_UPDATED,
            self::LOCALE_DETECTED,
        ];
    }
}
