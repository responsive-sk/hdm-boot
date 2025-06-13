<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Services;

use MvaBootstrap\Modules\Core\Language\Contracts\Services\LocaleServiceInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Locale Service - Enterprise Language Management.
 *
 * Based on samuelgfeller LocaleConfigurator with enterprise enhancements.
 * Implements LocaleServiceInterface for module isolation.
 */
final class LocaleService implements LocaleServiceInterface
{
    private array $config;

    private array $availableLocales;

    private string $defaultLocale;

    private string $currentLocale;

    private string $translationsPath;

    public function __construct(
        private readonly Paths $paths,
        private readonly LoggerInterface $logger
    ) {
        // Load language configuration
        $this->config = require $this->paths->base() . '/config/language.php';

        // Initialize settings from config
        $this->defaultLocale = $this->config['default_locale'];
        $this->currentLocale = $this->defaultLocale;
        $this->translationsPath = $this->paths->base() . '/' . $this->config['translations']['path'];

        // Get enabled locales only
        $this->availableLocales = array_keys(
            array_filter($this->config['available_locales'], fn ($locale) => $locale['enabled'])
        );

        $this->ensureTranslationsDirectory();
        $this->setDefaultTimezone();
    }

    /**
     * Set application language/locale.
     *
     * @param string|null $locale Locale code (e.g., 'sk_SK', 'en_US')
     * @param string $domain Text domain for gettext (default: 'messages')
     *
     * @return bool|string New locale string or false on failure
     */
    public function setLanguage(?string $locale, string $domain = 'messages'): bool|string
    {
        try {
            $codeset = 'UTF-8';

            // Normalize locale (replace hyphen with underscore)
            $locale = $this->normalizeLocale($locale);

            // Get available locale (with fallback)
            $locale = $this->getAvailableLocale($locale);

            // Set system locale
            $localeWithHyphen = str_replace('_', '-', $locale);
            $setLocaleResult = setlocale(LC_ALL, $locale, $localeWithHyphen);

            // Setup gettext if translation file exists
            $this->setupGettext($locale, $domain, $codeset);

            $this->currentLocale = $locale;

            $this->logger->info('Language set successfully', [
                'locale'           => $locale,
                'domain'           => $domain,
                'setlocale_result' => $setLocaleResult,
            ]);

            return $setLocaleResult;
        } catch (\Exception $e) {
            $this->logger->error('Failed to set language', [
                'locale' => $locale,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            // Error already logged via logger service

            return false;
        }
    }

    /**
     * Get current locale.
     */
    public function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Get language code from current locale.
     */
    public function getCurrentLanguageCode(): string
    {
        return $this->getLanguageCodeFromLocale($this->currentLocale) ?? 'en';
    }

    /**
     * Get language code for template paths.
     * Returns empty string for English (default), language code with slash for others.
     */
    public function getLanguageCodeForPath(): string
    {
        $langCode = $this->getCurrentLanguageCode();

        // English is default, no subdirectory needed
        return $langCode === 'en' ? '' : $langCode . '/';
    }

    /**
     * Get all available locales.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * Check if locale is supported.
     */
    public function isLocaleSupported(string $locale): bool
    {
        $normalizedLocale = $this->normalizeLocale($locale);

        return in_array($normalizedLocale, $this->availableLocales, true);
    }

    /**
     * Get locale display name.
     */
    public function getLocaleDisplayName(string $locale): string
    {
        return $this->config['available_locales'][$locale]['name'] ?? $locale;
    }

    /**
     * Get locale native name.
     */
    public function getLocaleNativeName(string $locale): string
    {
        return $this->config['available_locales'][$locale]['native_name'] ?? $locale;
    }

    /**
     * Get locale flag emoji.
     */
    public function getLocaleFlag(string $locale): string
    {
        return $this->config['available_locales'][$locale]['flag'] ?? '🌍';
    }

    /**
     * Translate string using gettext with fallback.
     */
    public function translate(string $key, array $parameters = []): string
    {
        // Try gettext first, fallback to original message
        $translated = function_exists('__') ? __($key) : $key;

        // If translation is empty or same as original, use original
        if (empty($translated) || $translated === $key) {
            $translated = $key;
        }

        // If parameters provided, replace placeholders
        if (!empty($parameters)) {
            // Support both named placeholders {name} and sprintf-style %s
            foreach ($parameters as $placeholder => $value) {
                // Replace named placeholders like {name}
                $translated = str_replace('{' . $placeholder . '}', (string) $value, $translated);
                // Replace sprintf-style placeholders like %s (for backward compatibility)
                $translated = preg_replace('/%s/', (string) $value, $translated, 1);
            }
        }

        return $translated;
    }

    /**
     * Translate plural string using gettext with fallback.
     */
    public function translatePlural(string $singular, string $plural, int $count, ...$args): string
    {
        // Try gettext first, fallback to simple logic
        if (function_exists('ngettext')) {
            $translated = ngettext($singular, $plural, $count);
        } else {
            // Simple fallback: use singular for 1, plural for others
            $translated = $count === 1 ? $singular : $plural;
        }

        // If arguments provided, use sprintf formatting
        if (!empty($args)) {
            return sprintf($translated, ...$args);
        }

        return $translated;
    }

    /**
     * Normalize locale string.
     */
    private function normalizeLocale(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        // Replace hyphen with underscore
        return str_contains($locale, '-') ? str_replace('-', '_', $locale) : $locale;
    }

    /**
     * Get available locale with fallback.
     */
    private function getAvailableLocale(?string $locale): string
    {
        // If locale is available, return it
        if ($locale && in_array($locale, $this->availableLocales, true)) {
            return $locale;
        }

        // Try to find locale with same language but different region
        if ($locale) {
            $languageCode = $this->getLanguageCodeFromLocale($locale);

            foreach ($this->availableLocales as $availableLocale) {
                if ($this->getLanguageCodeFromLocale($availableLocale) === $languageCode) {
                    return $availableLocale;
                }
            }
        }

        // Return default locale
        return $this->defaultLocale;
    }

    /**
     * Get language code from locale.
     */
    private function getLanguageCodeFromLocale(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $locale = $this->normalizeLocale($locale);

        return $locale ? explode('_', $locale)[0] : null;
    }

    /**
     * Setup gettext for translations.
     */
    private function setupGettext(string $locale, string $domain, string $codeset): void
    {
        // Check for translation file
        $translationFile = sprintf(
            '%s/%s/LC_MESSAGES/%s_%s.mo',
            $this->translationsPath,
            $locale,
            $domain,
            $locale
        );

        // For non-English locales, require translation file
        if ($locale !== 'en_US' && !file_exists($translationFile)) {
            $this->logger->warning('Translation file not found', [
                'locale' => $locale,
                'file'   => $translationFile,
            ]);

            // Don't throw exception, just log warning and continue with English
            return;
        }

        // Generate text domain
        $textDomain = sprintf('%s_%s', $domain, $locale);

        // Set up gettext
        bindtextdomain($textDomain, $this->translationsPath);
        bind_textdomain_codeset($textDomain, $codeset);
        textdomain($textDomain);
    }

    /**
     * Ensure translations directory exists.
     */
    private function ensureTranslationsDirectory(): void
    {
        if (!is_dir($this->translationsPath)) {
            mkdir($this->translationsPath, 0o755, true);

            $this->logger->info('Created translations directory', [
                'path' => $this->translationsPath,
            ]);
        }
    }

    /**
     * Set default timezone from config.
     */
    private function setDefaultTimezone(): void
    {
        $timezone = $this->config['default_timezone'];

        if (date_default_timezone_set($timezone)) {
            $this->logger->debug('Timezone set successfully', ['timezone' => $timezone]);
        } else {
            $this->logger->warning('Failed to set timezone, using default', ['timezone' => $timezone]);
        }
    }

    /**
     * Get language configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get specific config value.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set current locale.
     */
    public function setCurrentLocale(string $locale): void
    {
        $this->setLanguage($locale);
    }

    /**
     * Get locale from user preferences.
     *
     * @param array<string, mixed> $user
     */
    public function getLocaleFromUser(array $user): string
    {
        $userLocale = $user['locale'] ?? $user['language'] ?? null;

        return $this->getAvailableLocale($userLocale);
    }

    /**
     * Get locale from request headers.
     */
    public function getLocaleFromRequest(string $acceptLanguageHeader): string
    {
        // Parse Accept-Language header
        $locales = [];
        $parts = explode(',', $acceptLanguageHeader);

        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^([a-z]{2}(?:-[A-Z]{2})?)(?:;q=([0-9.]+))?$/', $part, $matches)) {
                $locale = $matches[1];
                $quality = isset($matches[2]) ? (float) $matches[2] : 1.0;
                $locales[$locale] = $quality;
            }
        }

        // Sort by quality
        arsort($locales);

        // Find best matching locale
        foreach (array_keys($locales) as $locale) {
            $normalizedLocale = $this->normalizeLocale($locale);
            if ($this->isLocaleSupported($normalizedLocale)) {
                return $normalizedLocale;
            }
        }

        return $this->getDefaultLocale();
    }

    /**
     * Get default locale.
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Get all translations for current locale.
     *
     * @return array<string, string>
     */
    public function getAllTranslations(): array
    {
        // This would typically load all translations from files
        // For now, return empty array as translations are handled by gettext
        return [];
    }
}
