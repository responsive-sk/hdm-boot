<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Application\Queries;

use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\TranslationKey;

/**
 * Get Translation Query.
 *
 * Query for retrieving translations.
 */
final readonly class GetTranslationQuery
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public TranslationKey $key,
        public Locale $locale,
        public array $parameters = [],
        public ?Locale $fallbackLocale = null
    ) {
    }

    /**
     * Create query.
     *
     * @param array<string, string> $parameters
     */
    public static function create(
        string $key,
        string $localeCode,
        array $parameters = [],
        ?string $fallbackLocaleCode = null
    ): self {
        return new self(
            key: TranslationKey::fromString($key),
            locale: Locale::fromString($localeCode),
            parameters: $parameters,
            fallbackLocale: $fallbackLocaleCode ? Locale::fromString($fallbackLocaleCode) : null
        );
    }
}
