<?php

/**
 * HealthChecks Configuration.
 *
 * Provides DI container bindings for HealthChecks infrastructure.
 */

declare(strict_types=1);

use DI\Container;
use HdmBoot\SharedKernel\HealthChecks\Infrastructure\HealthCheckRegistry;
use Psr\Log\LoggerInterface;

return [
    // === SETTINGS ===

    'settings' => [
        'health_checks' => [
            'enabled'         => true,
            'default_timeout' => 30, // seconds
            'auto_register'   => true,
            'categories'      => [
                'infrastructure' => 'Infrastructure components',
                'database'       => 'Database connections',
                'filesystem'     => 'File system access',
                'external'       => 'External services',
                'application'    => 'Application components',
            ],
        ],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // Health Check Registry
        HealthCheckRegistry::class => function (Container $container): HealthCheckRegistry {
            $logger = $container->get(LoggerInterface::class);
            if (!$logger instanceof LoggerInterface) {
                throw new \RuntimeException('Logger service not properly configured');
            }

            return new HealthCheckRegistry($logger);
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        HealthCheckRegistry::class,
    ],

    // === INITIALIZATION ===

    'initialize' => function (Container $container): void {
        // Auto-register health checks if enabled
        $settings = $container->get('settings');
        if (
            is_array($settings) &&
            is_array($settings['health_checks'] ?? null) &&
            ($settings['health_checks']['auto_register'] ?? true)
        ) {
            $registry = $container->get(HealthCheckRegistry::class);
            if ($registry instanceof HealthCheckRegistry) {
                // Register built-in health checks
                // Note: Specific health checks will be registered by their respective modules
            }
        }
    },

    // === HEALTH CHECK ===

    'health_check' => function (Container $container): array {
        $registry = $container->get(HealthCheckRegistry::class);
        if (!$registry instanceof HealthCheckRegistry) {
            return [
                'registry_available' => false,
                'error'              => 'HealthCheckRegistry not available',
            ];
        }

        return [
            'registry_available' => true,
            'registered_checks'  => $registry->getCount(),
            'categories'         => $registry->getCategories(),
            'last_check'         => date('Y-m-d H:i:s'),
        ];
    },

    // === MODULE INFO ===

    'info' => [
        'name'        => 'HealthChecks',
        'description' => 'Health check infrastructure with registry pattern',
        'version'     => '1.0.0',
        'features'    => [
            'Central health check registry',
            'Category-based organization',
            'Tag-based filtering',
            'Critical vs non-critical checks',
            'Timeout handling',
            'Aggregated reporting',
            'JSON serialization',
            'Extensible architecture',
        ],
        'contracts' => [
            'HealthCheckInterface' => 'Contract for health check implementations',
        ],
        'value_objects' => [
            'HealthStatus'      => 'Health status enumeration',
            'HealthCheckResult' => 'Individual health check result',
            'HealthCheckReport' => 'Aggregated health check report',
        ],
    ],
];
