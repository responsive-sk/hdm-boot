<?php

declare(strict_types=1);

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->group('/api', function ($group) {
        // API status endpoint
        $group->get('/status', function (ServerRequestInterface $request, ResponseInterface $response) {
            /** @var Container $container */
            $container = $this->get(\DI\Container::class);
            $settings = $container->get('settings');

            $data = [
                'status'      => 'ok',
                'app'         => $settings['app']['name'],
                'version'     => $settings['app']['version'],
                'timestamp'   => date('c'),
                'environment' => $_ENV['APP_ENV'] ?? 'dev',
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: "{}");

            return $response->withHeader('Content-Type', 'application/json');
        })->setName('api.status');

        // API info endpoint
        $group->get('/info', function (ServerRequestInterface $request, ResponseInterface $response) {
            /** @var Container $container */
            $container = $this->get(\DI\Container::class);
            $pathHelper = $container->get(\MvaBootstrap\Shared\Helpers\SecurePathHelper::class);

            $data = [
                'app' => [
                    'name'        => 'MVA Bootstrap Application',
                    'version'     => '1.0.0',
                    'description' => 'Modular PHP application with secure paths',
                ],
                'features' => [
                    'modular_architecture' => true,
                    'secure_paths'         => true,
                    'dependency_injection' => true,
                    'environment_config'   => true,
                    'logging'              => true,
                    'sessions'             => true,
                    'database'             => true,
                ],
                'security' => [
                    'allowed_directories'       => $pathHelper->getAllowedDirectories(),
                    'path_traversal_protection' => true,
                    'file_upload_validation'    => true,
                ],
                'timestamp' => date('c'),
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: "{}");

            return $response->withHeader('Content-Type', 'application/json');
        })->setName('api.info');

        // Language/Localization routes
        $group->map(['GET', 'POST'], '/translate', \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction::class);
        $group->map(['GET', 'POST'], '/language', \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction::class);
    });
};
