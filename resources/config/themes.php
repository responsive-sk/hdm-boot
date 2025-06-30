<?php

declare(strict_types=1);

/**
 * Theme Configuration
 * 
 * Configuration for HDM Boot theme system.
 */

return [
    // Default active theme
    'default' => 'default',
    
    // Available themes
    'themes' => [
        'default' => [
            'name' => 'Default Theme',
            'description' => 'Modern theme with Tailwind CSS, GSAP animations, and Alpine.js reactivity',
            'version' => '1.0.0',
            'author' => 'HDM Boot Team',
            'stack' => [
                'css' => 'Tailwind CSS',
                'js' => 'Alpine.js',
                'animations' => 'GSAP',
                'build' => 'Vite',
                'package_manager' => 'pnpm'
            ],
            'features' => [
                'responsive' => true,
                'dark_mode' => false,
                'animations' => true,
                'components' => true,
                'utilities' => true,
                'forms' => true,
                'typography' => true
            ],
            'dependencies' => [
                'tailwindcss' => '^3.3.6',
                'alpinejs' => '^3.13.3',
                'gsap' => '^3.12.2',
                '@tailwindcss/forms' => '^0.5.7',
                '@tailwindcss/typography' => '^0.5.10',
                '@tailwindcss/aspect-ratio' => '^0.4.2'
            ],
            'dev_dependencies' => [
                'vite' => '^5.0.0',
                'postcss' => '^8.4.32',
                'autoprefixer' => '^10.4.16'
            ]
        ],
        
        'bootstrap' => [
            'name' => 'Bootstrap Theme',
            'description' => 'Classic Bootstrap theme with jQuery components',
            'version' => '1.0.0',
            'author' => 'HDM Boot Team',
            'stack' => [
                'css' => 'Bootstrap',
                'js' => 'jQuery',
                'animations' => 'CSS Transitions',
                'build' => 'Vite',
                'package_manager' => 'pnpm'
            ],
            'features' => [
                'responsive' => true,
                'dark_mode' => true,
                'animations' => false,
                'components' => true,
                'utilities' => true,
                'forms' => true,
                'typography' => false
            ],
            'dependencies' => [
                'bootstrap' => '^5.3.0',
                'jquery' => '^3.7.0',
                '@popperjs/core' => '^2.11.8'
            ],
            'dev_dependencies' => [
                'vite' => '^5.0.0',
                'sass' => '^1.69.0'
            ]
        ],
        
        'minimal' => [
            'name' => 'Minimal Theme',
            'description' => 'Lightweight theme with pure CSS and vanilla JavaScript',
            'version' => '1.0.0',
            'author' => 'HDM Boot Team',
            'stack' => [
                'css' => 'Pure CSS',
                'js' => 'Vanilla JS',
                'animations' => 'CSS Animations',
                'build' => 'Vite',
                'package_manager' => 'pnpm'
            ],
            'features' => [
                'responsive' => true,
                'dark_mode' => false,
                'animations' => true,
                'components' => false,
                'utilities' => false,
                'forms' => false,
                'typography' => false
            ],
            'dependencies' => [],
            'dev_dependencies' => [
                'vite' => '^5.0.0'
            ]
        ]
    ],
    
    // Theme paths
    'paths' => [
        'themes' => 'resources/themes',
        'assets' => 'public/assets/themes',
        'views' => 'resources/themes/{theme}/views',
        'build' => 'public/assets/themes/{theme}'
    ],
    
    // Build configuration
    'build' => [
        'command' => 'pnpm run build',
        'dev_command' => 'pnpm run dev',
        'watch_command' => 'pnpm run watch',
        'install_command' => 'pnpm install',
        'output_dir' => 'public/assets/themes/{theme}',
        'manifest_file' => '.vite/manifest.json'
    ],
    
    // Asset configuration
    'assets' => [
        'css_entry' => 'assets/css/app.css',
        'js_entry' => 'assets/js/app.js',
        'versioning' => true,
        'minification' => true,
        'source_maps' => false
    ],
    
    // Development configuration
    'development' => [
        'hot_reload' => true,
        'dev_server_port' => 5173,
        'dev_server_host' => 'localhost',
        'proxy_api' => true,
        'proxy_target' => 'http://localhost:8000'
    ],
    
    // Theme switching
    'switching' => [
        'enabled' => true,
        'cookie_name' => 'hdm_boot_theme',
        'cookie_lifetime' => 30 * 24 * 60 * 60, // 30 days
        'session_key' => 'theme',
        'url_parameter' => 'theme'
    ],
    
    // Cache configuration
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key_prefix' => 'theme_',
        'invalidate_on_build' => true
    ],
    
    // Security
    'security' => [
        'allowed_themes' => null, // null = all available themes
        'validate_theme_files' => true,
        'sanitize_theme_names' => true
    ]
];
