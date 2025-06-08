<?php

declare(strict_types=1);

use MvaBootstrap\Bootstrap\App;

// Define root path
$rootPath = dirname(__DIR__);

// Autoloader
require_once $rootPath . '/vendor/autoload.php';

// Create and run application
$app = new App($rootPath);
$app->run();
