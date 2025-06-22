<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Domain\Services;

use HdmBoot\Modules\Core\Language\Domain\Contracts\TranslationRepositoryInterface;
use HdmBoot\Modules\Core\Language\Domain\Models\Translation;
use HdmBoot\Modules\Core\Language\Domain\ValueObjects\Locale;
use HdmBoot\Modules\Core\Language\Domain\ValueObjects\TranslationKey;

/**
 * Translation Domain Service.
 *
 * Contains pure business logic for translation operations.
 */
final class TranslationService
{
    public function __construct(
        private readonly TranslationRepositoryInterface $translationRepository
    ) {
    }

    /**
     * Translate a key with parameters.
     *
     * @param array<string, string> $parameters
     */
    public function translate(
        TranslationKey $key,
        Locale $locale,
        array $parameters = [],
        ?Locale $fallbackLocale = null
    ): string {
        // Try to find translation in requested locale
        $translation = $this->translationRepository->findByKeyAndLocale($key, $locale);

        // If not found and fallback locale provided, try fallback
        if (!$translation && $fallbackLocale && !$locale->equals($fallbackLocale)) {
            $translation = $this->translationRepository->findByKeyAndLocale($key, $fallbackLocale);
        }

        // If still not found, return the key itself
        if (!$translation) {
            return $key->toString();
        }

        // Interpolate parameters
        return $translation->interpolate($parameters);
    }

    /**
     * Get all translations for a locale.
     *
     * @return array<string, string>
     */
    public function getAllTranslations(Locale $locale): array
    {
        $translations = $this->translationRepository->findByLocale($locale);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->getKey()->toString()] = $translation->getValue();
        }

        return $result;
    }

    /**
     * Add or update translation.
     */
    public function setTranslation(
        TranslationKey $key,
        Locale $locale,
        string $value
    ): Translation {
        $existingTranslation = $this->translationRepository->findByKeyAndLocale($key, $locale);

        if ($existingTranslation) {
            $updatedTranslation = $existingTranslation->updateValue($value);

            return $this->translationRepository->save($updatedTranslation);
        }

        $newTranslation = Translation::create($key, $locale, $value);

        return $this->translationRepository->save($newTranslation);
    }

    /**
     * Remove translation.
     */
    public function removeTranslation(TranslationKey $key, Locale $locale): bool
    {
        return $this->translationRepository->delete($key, $locale);
    }

    /**
     * Check if translation exists.
     */
    public function hasTranslation(TranslationKey $key, Locale $locale): bool
    {
        return $this->translationRepository->exists($key, $locale);
    }

    /**
     * Get available locales.
     *
     * @return array<Locale>
     */
    public function getAvailableLocales(): array
    {
        return $this->translationRepository->getAvailableLocales();
    }

    /**
     * Get translation statistics for locale.
     *
     * @return array<string, mixed>
     */
    public function getTranslationStats(Locale $locale, Locale $defaultLocale): array
    {
        $totalTranslations = $this->translationRepository->getTranslationCount($locale);
        $missingTranslations = $this->translationRepository->getMissingTranslations($locale, $defaultLocale);
        $defaultTranslations = $this->translationRepository->getTranslationCount($defaultLocale);

        $completionPercentage = $defaultTranslations > 0
            ? round(($totalTranslations / $defaultTranslations) * 100, 2)
            : 0;

        return [
            'locale'                => $locale->toString(),
            'total_translations'    => $totalTranslations,
            'missing_translations'  => count($missingTranslations),
            'completion_percentage' => $completionPercentage,
            'missing_keys'          => array_map(fn ($key) => $key->toString(), $missingTranslations),
        ];
    }

    /**
     * Validate translation parameters.
     *
     * @param array<string, string> $parameters
     *
     * @return array<string> Missing parameter names
     */
    public function validateTranslationParameters(
        TranslationKey $key,
        Locale $locale,
        array $parameters
    ): array {
        $translation = $this->translationRepository->findByKeyAndLocale($key, $locale);

        if (!$translation) {
            return [];
        }

        $requiredParameters = $translation->getParameterNames();
        $providedParameters = array_keys($parameters);

        return array_diff($requiredParameters, $providedParameters);
    }
}
