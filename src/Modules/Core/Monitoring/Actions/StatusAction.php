<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StatusAction
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private array $settings
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Safe access to settings with proper type checking
        $appSettings = isset($this->settings['app']) && is_array($this->settings['app'])
            ? $this->settings['app']
            : [];

        $status = [
            'status' => 'OK',
            'timestamp' => time(),
            'version' => $appSettings['version'] ?? '1.0.0',
            'app' => [
                'name' => $appSettings['name'] ?? 'MVA Bootstrap',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => $appSettings['debug'] ?? false,
                'timezone' => $appSettings['timezone'] ?? 'UTC'
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'timezone' => date_default_timezone_get()
            ]
        ];

        $jsonContent = json_encode($status, JSON_PRETTY_PRINT);
        if ($jsonContent === false) {
            $jsonContent = '{"status":"error","message":"Failed to encode status"}';
        }

        $response->getBody()->write($jsonContent);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
