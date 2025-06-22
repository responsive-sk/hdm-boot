<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Application\Commands;

use HdmBoot\Modules\Core\Language\Domain\ValueObjects\Locale;

/**
 * Change Locale Command.
 *
 * Command for changing user's locale.
 */
final readonly class ChangeLocaleCommand
{
    public function __construct(
        public Locale $newLocale,
        public ?string $userId = null,
        public ?Locale $previousLocale = null
    ) {
    }

    /**
     * Create command.
     */
    public static function create(
        string $newLocaleCode,
        ?string $userId = null,
        ?string $previousLocaleCode = null
    ): self {
        return new self(
            newLocale: Locale::fromString($newLocaleCode),
            userId: $userId,
            previousLocale: $previousLocaleCode ? Locale::fromString($previousLocaleCode) : null
        );
    }
}
