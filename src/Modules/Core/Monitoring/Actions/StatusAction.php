<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Monitoring\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StatusAction
{
    public function __construct(
        private array $settings
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $status = [
            'status' => 'OK',
            'timestamp' => time(),
            'version' => $this->settings['app']['version'] ?? '1.0.0',
            'app' => [
                'name' => $this->settings['app']['name'] ?? 'MVA Bootstrap',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => $this->settings['app']['debug'] ?? false,
                'timezone' => $this->settings['app']['timezone'] ?? 'UTC'
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'timezone' => date_default_timezone_get()
            ]
        ];

        $response->getBody()->write(json_encode($status, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
