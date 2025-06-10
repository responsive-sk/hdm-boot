<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction;
use MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction;
use MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

/**
 * Consolidated Application Routes.
 *
 * All routes in one file for simplified configuration.
 */
return function (App $app): void {
    // ===== HOME ROUTES =====
    $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVA Bootstrap Application</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 40px; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .feature { padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .links { text-align: center; margin-top: 40px; }
        .links a { margin: 0 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 MVA Bootstrap Application</h1>
        <p>Modular PHP application with enterprise architecture</p>
    </div>

    <div class="features">
        <div class="feature">
            <h3>🏗️ Modular Architecture</h3>
            <p>Clean separation of concerns with Core and Optional modules</p>
        </div>
        <div class="feature">
            <h3>🔐 Security First</h3>
            <p>Authentication, CSRF protection, secure sessions</p>
        </div>
        <div class="feature">
            <h3>🌍 Internationalization</h3>
            <p>Multi-language support with locale detection</p>
        </div>
        <div class="feature">
            <h3>📊 Enterprise Ready</h3>
            <p>Logging, monitoring, database abstraction</p>
        </div>
    </div>

    <div class="links">
        <a href="/login">Login</a>
        <a href="/profile">Profile</a>
        <a href="/api/status">API Status</a>
    </div>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('home');

    // ===== WEB ROUTES =====
    $app->get('/login', LoginPageAction::class)->setName('login');
    $app->post('/login', LoginSubmitAction::class)->setName('login-submit');
    $app->get('/logout', LogoutAction::class)->setName('logout-get');
    $app->post('/logout', LogoutAction::class)->setName('logout');

    // Protected routes
    $app->get('/profile', ProfilePageAction::class)
        ->setName('profile')
        ->add(UserAuthenticationMiddleware::class);

    // ===== API ROUTES =====
    $app->group('/api', function ($group) {
        // Status endpoint
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

        // Language API
        $group->map(['GET', 'POST'], '/translate', \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction::class);
        $group->map(['GET', 'POST'], '/language', \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction::class);
    });
};
