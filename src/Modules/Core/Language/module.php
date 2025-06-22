<?php

/**
 * Language Module Manifest
 *
 * This file defines the Language module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name' => 'Language',
    'version' => '1.0.0',
    'description' => 'Internationalization and localization services with multi-language support',
    'dependencies' => ['User'], // Depends on User for user locale preferences
    'config' => 'config.php',
    'routes' => null, // Language routes are defined in config.php
    'authors' => [
        'HDM Boot Team'
    ],
    'tags' => [
        'i18n',
        'l10n',
        'localization',
        'internationalization',
        'translation',
        'locale'
    ],
    'provides' => [
        'localization',
        'translation-services',
        'locale-detection',
        'language-switching'
    ],
    'requires' => [
        'php' => '>=8.1',
        'ext-intl' => '*'
    ],
    'enabled' => true
];
