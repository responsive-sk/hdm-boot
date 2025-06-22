#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Route List Command.
 *
 * Display all registered routes in the application.
 * Usage: php bin/route-list.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\Boot\App;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

try {
    echo "🛣️  HDM Boot Route List\n";
    echo str_repeat('=', 80) . "\n";
    
    // Initialize application
    $app = new App();
    $app->initialize();
    $slimApp = $app->getSlimApp();
    
    // Get route collector
    $routeCollector = $slimApp->getRouteCollector();
    $routes = $routeCollector->getRoutes();
    
    if (empty($routes)) {
        echo "No routes found.\n";
        exit(0);
    }
    
    echo sprintf("📋 Found %d routes:\n\n", count($routes));
    
    // Group routes by pattern for better readability
    $groupedRoutes = [];
    foreach ($routes as $route) {
        $pattern = $route->getPattern();
        $methods = $route->getMethods();
        $callable = $route->getCallable();
        
        // Get callable name
        if (is_string($callable)) {
            $callableName = $callable;
        } elseif (is_array($callable) && count($callable) === 2) {
            $callableName = (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]) . '::' . $callable[1];
        } elseif (is_object($callable)) {
            $callableName = get_class($callable);
        } else {
            $callableName = 'Unknown';
        }
        
        // Shorten class names for readability
        $shortCallableName = $callableName;
        if (str_contains($callableName, 'HdmBoot\\Modules\\Core\\')) {
            $parts = explode('\\', $callableName);
            $shortCallableName = end($parts);
        }
        
        $groupedRoutes[] = [
            'methods' => implode('|', $methods),
            'pattern' => $pattern,
            'callable' => $shortCallableName,
            'full_callable' => $callableName,
        ];
    }
    
    // Sort routes by pattern
    usort($groupedRoutes, function ($a, $b) {
        return strcmp($a['pattern'], $b['pattern']);
    });
    
    // Display routes in table format
    printf("%-10s %-30s %s\n", 'METHOD', 'PATTERN', 'HANDLER');
    echo str_repeat('-', 80) . "\n";
    
    foreach ($groupedRoutes as $route) {
        printf("%-10s %-30s %s\n", 
            $route['methods'], 
            $route['pattern'], 
            $route['callable']
        );
    }
    
    echo str_repeat('-', 80) . "\n";
    echo sprintf("Total: %d routes\n\n", count($routes));
    
    // Show route categories
    $categories = [
        'Web Pages' => [],
        'API Endpoints' => [],
        'Health Checks' => [],
        'Documentation' => [],
        'Other' => [],
    ];
    
    foreach ($groupedRoutes as $route) {
        if (str_starts_with($route['pattern'], '/api/')) {
            $categories['API Endpoints'][] = $route;
        } elseif (str_contains($route['pattern'], 'status') || str_contains($route['pattern'], 'health') || str_contains($route['pattern'], 'ping')) {
            $categories['Health Checks'][] = $route;
        } elseif (str_contains($route['pattern'], 'docs')) {
            $categories['Documentation'][] = $route;
        } elseif ($route['callable'] === 'Closure') {
            $categories['Other'][] = $route;
        } else {
            $categories['Web Pages'][] = $route;
        }
    }
    
    echo "📊 Route Categories:\n";
    foreach ($categories as $category => $routes) {
        if (!empty($routes)) {
            echo sprintf("  %s: %d routes\n", $category, count($routes));
        }
    }
    
    echo "\n🔧 Testing Commands:\n";
    echo "  curl http://localhost/_status     # Health check\n";
    echo "  curl http://localhost/docs        # Documentation\n";
    echo "  curl http://localhost/api/status  # API status\n";
    echo "  curl http://localhost/            # Home page\n";
    
    echo "\n✅ Route list generated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
