<?php

declare(strict_types=1);

use Slim\App;

/*
 * MVA Bootstrap Application Routes Loader.
 *
 * This file loads all route definitions from core application route files.
 * Module routes are loaded automatically by ModuleManager to avoid duplicates.
 */
return function (App $app): void {
    // Load core application route files
    $routeFiles = [
        __DIR__ . '/routes/home.php',
        __DIR__ . '/routes/api.php',
        __DIR__ . '/routes/test.php',
    ];

    // Module routes are loaded automatically by ModuleManager
    // No need to load them here to avoid duplicates

    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            $routes = require $routeFile;
            if (is_callable($routes)) {
                $routes($app);
            }
        }
    }
};
