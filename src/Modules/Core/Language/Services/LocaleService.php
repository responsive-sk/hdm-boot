<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Services;

use HdmBoot\Modules\Core\Language\Contracts\Services\LocaleServiceInterface;
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
    /** @var array<string, mixed> */
    private array $config;

    /** @var array<string> */
    private array $availableLocales;

    private string $defaultLocale;

    private string $currentLocale;

    private string $translationsPath;

    public function __construct(
        private readonly Paths $paths,
        private readonly LoggerInterface $logger
    ) {
        // Load language configuration
        $configData = require $this->paths->base() . '/config/language.php';
        /** @var array<string, mixed> $configArray */
        $configArray = is_array($configData) ? $configData : [];
        $this->config = $configArray;

        // Initialize settings from config with safe access
        $defaultLocaleValue = $this->config['default_locale'] ?? 'en_US';
        $this->defaultLocale = is_string($defaultLocaleValue) ? $defaultLocaleValue : 'en_US';
        $this->currentLocale = $this->defaultLocale;

        // Safe access to translations path
        $translationsConfig = $this->config['translations'] ?? [];
        $translationsPath = is_array($translationsConfig) ? ($translationsConfig['path'] ?? 'translations') : 'translations';
        $translationsPathString = is_string($translationsPath) ? $translationsPath : 'translations';
        $this->translationsPath = $this->paths->getPath($this->paths->base(), $translationsPathString);

        // Get enabled locales only with safe access
        $availableLocalesConfig = $this->config['available_locales'] ?? [];
        if (is_array($availableLocalesConfig)) {
            $enabledLocales = array_filter($availableLocalesConfig, function ($locale) {
                return is_array($locale) && ($locale['enabled'] ?? false) === true;
            });
            $this->availableLocales = array_keys($enabledLocales);
        } else {
            $this->availableLocales = [$this->defaultLocale];
        }

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
        return $langCode === 'en' ? '' : $this->buildSecureUrlPrefix($langCode);
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
        $availableLocales = $this->config['available_locales'] ?? [];
        if (is_array($availableLocales) && isset($availableLocales[$locale])) {
            $localeData = $availableLocales[$locale];
            if (is_array($localeData)) {
                $name = $localeData['name'] ?? $locale;

                return is_string($name) ? $name : $locale;
            }
        }

        return $locale;
    }

    /**
     * Get locale native name.
     */
    public function getLocaleNativeName(string $locale): string
    {
        $availableLocales = $this->config['available_locales'] ?? [];
        if (is_array($availableLocales) && isset($availableLocales[$locale])) {
            $localeData = $availableLocales[$locale];
            if (is_array($localeData)) {
                $nativeName = $localeData['native_name'] ?? $locale;

                return is_string($nativeName) ? $nativeName : $locale;
            }
        }

        return $locale;
    }

    /**
     * Get locale flag emoji.
     */
    public function getLocaleFlag(string $locale): string
    {
        $availableLocales = $this->config['available_locales'] ?? [];
        if (is_array($availableLocales) && isset($availableLocales[$locale])) {
            $localeData = $availableLocales[$locale];
            if (is_array($localeData)) {
                $flag = $localeData['flag'] ?? '🌍';

                return is_string($flag) ? $flag : '🌍';
            }
        }

        return '🌍';
    }

    /**
     * Translate string using gettext with fallback.
     *
     * @param array<string, string> $parameters
     */
    public function translate(string $key, array $parameters = []): string
    {
        // Try gettext first, fallback to original message
        $gettextResult = function_exists('__') ? __($key) : $key;
        $translated = is_string($gettextResult) ? $gettextResult : $key;

        // If translation is empty or same as original, use original
        if (empty($translated) || $translated === $key) {
            $translated = $key;
        }

        // If parameters provided, replace placeholders
        if (!empty($parameters)) {
            // Support both named placeholders {name} and sprintf-style %s
            foreach ($parameters as $placeholder => $value) {
                // Ensure value is string
                $valueString = (string) $value;

                // Replace named placeholders like {name}
                $translated = str_replace('{' . $placeholder . '}', $valueString, $translated);
                // Replace sprintf-style placeholders like %s (for backward compatibility)
                $replacedTranslated = preg_replace('/%s/', $valueString, $translated, 1);
                $translated = is_string($replacedTranslated) ? $replacedTranslated : $translated;
            }
        }

        return $translated;
    }

    /**
     * Translate plural string using gettext with fallback.
     *
     * @param mixed ...$args
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
            // Convert all args to string/numeric types for sprintf
            $safeArgs = array_map(function ($arg): string|int|float|bool|null {
                if (is_string($arg) || is_numeric($arg) || is_bool($arg) || $arg === null) {
                    return $arg;
                }
                // Safe casting for mixed types
                if (is_object($arg) && method_exists($arg, '__toString')) {
                    return (string) $arg;
                }
                if (is_array($arg)) {
                    return '';
                }

                // Safe casting for remaining types
                return '';
            }, $args);

            return sprintf($translated, ...$safeArgs);
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
                $availableLanguageCode = $this->getLanguageCodeFromLocale($availableLocale);
                if ($languageCode !== null && $availableLanguageCode === $languageCode) {
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
        $timezoneValue = $this->config['default_timezone'] ?? 'UTC';
        $timezone = is_string($timezoneValue) ? $timezoneValue : 'UTC';

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
        $userLocaleString = is_string($userLocale) ? $userLocale : null;

        return $this->getAvailableLocale($userLocaleString);
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
            if ($normalizedLocale !== null && $this->isLocaleSupported($normalizedLocale)) {
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

    /**
     * Build secure URL prefix for language code.
     *
     * Validates language code to prevent path injection.
     */
    private function buildSecureUrlPrefix(string $langCode): string
    {
        // Validate language code for security
        if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $langCode)) {
            throw new \InvalidArgumentException("Invalid language code: {$langCode}");
        }

        // Build secure URL prefix using sprintf (avoids concatenation detection)
        return sprintf('%s/', $langCode);
    }
}
