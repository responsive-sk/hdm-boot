<?php

declare(strict_types=1);

/**
 * Example Module Configuration Template
 *
 * This file defines the module's configuration including:
 * - Module metadata
 * - Dependencies
 * - Configuration parameters
 */
return [
    'module' => [
        'name' => 'Example',
        'version' => '1.0.0',
        'description' => 'Example module for MVA Bootstrap',
        'namespace' => 'HdmBoot\\Modules\\Example',
        'dependencies' => [
            'core' => '^1.0'
        ]
    ],

    'enabled' => true,

    'config' => [
        // Module specific configuration
        'example_setting' => $_ENV['EXAMPLE_SETTING'] ?? 'default_value',
        'cache_enabled' => ($_ENV['EXAMPLE_CACHE_ENABLED'] ?? 'false') === 'true',
        'timeout' => (int) ($_ENV['EXAMPLE_TIMEOUT'] ?? 30)
    ],

    'routes' => [
        'prefix' => '/example',
        'middleware' => [
            // Global middleware for all module routes
            'HdmBoot\\Modules\\Example\\Infrastructure\\Middleware\\ExampleMiddleware'
        ]
    ],

    'events' => [
        'listeners' => [
            // Event listener mappings
            'HdmBoot\\Modules\\Example\\Domain\\Events\\ExampleEvent' => [
                'HdmBoot\\Modules\\Example\\Infrastructure\\Listeners\\ExampleListener'
            ]
        ]
    ],

    'services' => [
        // Module service definitions
        'example.service' => [
            'class' => 'HdmBoot\\Modules\\Example\\Infrastructure\\Services\\ExampleService',
            'arguments' => [
                'config' => '%module.config%'
            ]
        ]
    ]
];
