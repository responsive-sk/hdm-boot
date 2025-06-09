<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // Test routes (available in non-production environments)
    if (($_ENV['APP_ENV'] ?? 'dev') !== 'prod') {
        $app->group('/test', function ($group) {
            // Path security testing
            $group->get('/paths', function (ServerRequestInterface $request, ResponseInterface $response) {
                $container = $this->get(\DI\Container::class);
                $pathHelper = $container->get(\MvaBootstrap\Shared\Helpers\SecurePathHelper::class);

                $tests = [];

                // Test 1: Valid path
                try {
                    $validPath = $pathHelper->securePath('test.txt', 'var');
                    $tests[] = ['test' => 'Valid path', 'result' => 'PASS', 'path' => $validPath];
                } catch (Exception $e) {
                    $tests[] = ['test' => 'Valid path', 'result' => 'FAIL', 'error' => $e->getMessage()];
                }

                // Test 2: Path traversal attempt
                try {
                    $pathHelper->securePath('../../../etc/passwd', 'var');
                    $tests[] = ['test' => 'Path traversal protection', 'result' => 'FAIL', 'error' => 'Should have blocked traversal'];
                } catch (Exception $e) {
                    $tests[] = ['test' => 'Path traversal protection', 'result' => 'PASS', 'blocked' => $e->getMessage()];
                }

                // Test 3: Forbidden path access
                try {
                    $pathHelper->securePath('config/container.php', 'public');
                    $tests[] = ['test' => 'Forbidden path protection', 'result' => 'FAIL', 'error' => 'Should have blocked access'];
                } catch (Exception $e) {
                    $tests[] = ['test' => 'Forbidden path protection', 'result' => 'PASS', 'blocked' => $e->getMessage()];
                }

                $data = [
                    'title'               => 'Path Security Tests',
                    'timestamp'           => date('c'),
                    'tests'               => $tests,
                    'allowed_directories' => $pathHelper->getAllowedDirectories(),
                ];

                $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));

                return $response->withHeader('Content-Type', 'application/json');
            })->setName('test.paths');

            // Environment info
            $group->get('/env', function (ServerRequestInterface $request, ResponseInterface $response) {
                $data = [
                    'php_version'        => PHP_VERSION,
                    'app_env'            => $_ENV['APP_ENV'] ?? 'dev',
                    'app_debug'          => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                    'loaded_extensions'  => get_loaded_extensions(),
                    'memory_limit'       => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                ];

                $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));

                return $response->withHeader('Content-Type', 'application/json');
            })->setName('test.env');
        });
    }
};
