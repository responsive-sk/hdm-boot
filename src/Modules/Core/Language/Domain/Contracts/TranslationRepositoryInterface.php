<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\Contracts;

use MvaBootstrap\Modules\Core\Language\Domain\Models\Translation;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\TranslationKey;

/**
 * Translation Repository Interface.
 *
 * Domain contract for translation persistence.
 */
interface TranslationRepositoryInterface
{
    /**
     * Find translation by key and locale.
     */
    public function findByKeyAndLocale(TranslationKey $key, Locale $locale): ?Translation;

    /**
     * Find all translations for a locale.
     *
     * @return array<Translation>
     */
    public function findByLocale(Locale $locale): array;

    /**
     * Find all translations for a key across all locales.
     *
     * @return array<Translation>
     */
    public function findByKey(TranslationKey $key): array;

    /**
     * Save translation.
     */
    public function save(Translation $translation): Translation;

    /**
     * Delete translation.
     */
    public function delete(TranslationKey $key, Locale $locale): bool;

    /**
     * Check if translation exists.
     */
    public function exists(TranslationKey $key, Locale $locale): bool;

    /**
     * Get all available locales with translations.
     *
     * @return array<Locale>
     */
    public function getAvailableLocales(): array;

    /**
     * Get translation count for locale.
     */
    public function getTranslationCount(Locale $locale): int;

    /**
     * Get missing translations for locale compared to default locale.
     *
     * @return array<TranslationKey>
     */
    public function getMissingTranslations(Locale $locale, Locale $defaultLocale): array;
}
