<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Contracts\Services;

/**
 * Locale Service Interface - Public API for Language Module.
 *
 * This interface defines the public API that other modules can use
 * to interact with the Language module's localization features.
 */
interface LocaleServiceInterface
{
    /**
     * Get current locale.
     */
    public function getCurrentLocale(): string;

    /**
     * Set current locale.
     */
    public function setCurrentLocale(string $locale): void;

    /**
     * Get all available locales.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array;

    /**
     * Check if locale is supported.
     */
    public function isLocaleSupported(string $locale): bool;

    /**
     * Get locale from user preferences.
     *
     * @param array<string, mixed> $user
     */
    public function getLocaleFromUser(array $user): string;

    /**
     * Get locale from request headers.
     */
    public function getLocaleFromRequest(string $acceptLanguageHeader): string;

    /**
     * Get default locale.
     */
    public function getDefaultLocale(): string;

    /**
     * Translate a key to current locale.
     */
    public function translate(string $key, array $parameters = []): string;

    /**
     * Get all translations for current locale.
     *
     * @return array<string, string>
     */
    public function getAllTranslations(): array;
}
