<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Security\Actions\Web\LoginPageAction;
use HdmBoot\Modules\Core\Security\Actions\Web\LoginSubmitAction;
use HdmBoot\Modules\Core\Security\Actions\Web\LogoutAction;
use HdmBoot\Modules\Core\Security\Infrastructure\Middleware\UserAuthenticationMiddleware;
use HdmBoot\Modules\Core\Session\Infrastructure\Middleware\SessionStartMiddleware;
use HdmBoot\Modules\Core\User\Actions\Web\ProfilePageAction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

/*
 * Consolidated Application Routes.
 *
 * All routes in one file for simplified configuration.
 */
return function (App $app): void {
    // Load API routes
    (require __DIR__ . '/routes/api.php')($app);

    // ===== HOME ROUTES =====
    $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HDM Boot Framework</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px; margin: 50px auto; padding: 20px;
            background: #f8fafc; color: #1a202c; line-height: 1.6;
        }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #2d3748; margin-bottom: 8px; }
        .header p { color: #4a5568; font-size: 1.1rem; }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .feature {
            padding: 24px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .feature h2 {
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 1.25rem;
        }
        .feature p {
            color: #4a5568;
            margin: 0;
        }
        .links {
            text-align: center;
            margin-top: 40px;
        }
        .links a {
            margin: 0 8px;
            padding: 12px 24px;
            background: #1a365d;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.2s;
            border: 2px solid #1a365d;
        }
        .links a:hover {
            background: #2c5282;
            border-color: #2c5282;
        }
        .links a:focus {
            outline: 3px solid #63b3ed;
            outline-offset: 2px;
            background: #2c5282;
        }
        .links a:active {
            background: #1a202c;
            transform: translateY(1px);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 HDM Boot Framework</h1>
        <p>Hexagonal + DDD + Modular Monolith Architecture</p>
    </div>

    <div class="features">
        <div class="feature">
            <h2>🏗️ Triple Architecture</h2>
            <p>Hexagonal Architecture + Domain-Driven Design + Modular Monolith for enterprise applications</p>
        </div>
        <div class="feature">
            <h2>🔐 Security First</h2>
            <p>JWT authentication, CSRF protection, secure sessions, and path-safe operations</p>
        </div>
        <div class="feature">
            <h2>🌍 Internationalization</h2>
            <p>Multi-language support with Slovak/Czech localization and automatic detection</p>
        </div>
        <div class="feature">
            <h2>📊 Enterprise Ready</h2>
            <p>Comprehensive logging, health monitoring, database abstraction, and production deployment</p>
        </div>
    </div>

    <nav class="links" role="navigation" aria-label="Main navigation">
        <a href="/blog" aria-label="Visit HDM Boot Blog">Blog</a>
        <a href="/login" aria-label="User Login Page">Login</a>
        <a href="/profile" aria-label="User Profile Page">Profile</a>
        <a href="/api/status" aria-label="API Status Monitor">API Status</a>
    </nav>
</body>
</html>';

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    })->setName('home');

    // ===== WEB ROUTES =====
    // Authentication routes (with session middleware)
    $app->get('/login', LoginPageAction::class)
        ->setName('login')
        ->add(SessionStartMiddleware::class);

    $app->post('/login', LoginSubmitAction::class)
        ->setName('login-submit')
        ->add(SessionStartMiddleware::class);

    $app->get('/logout', LogoutAction::class)
        ->setName('logout-get')
        ->add(SessionStartMiddleware::class);

    $app->post('/logout', LogoutAction::class)
        ->setName('logout')
        ->add(SessionStartMiddleware::class);

    // Protected routes (with session + authentication middleware)
    $app->get('/profile', ProfilePageAction::class)
        ->setName('profile')
        ->add(UserAuthenticationMiddleware::class)
        ->add(SessionStartMiddleware::class);

    // ===== BLOG ROUTES =====
    // Blog routes are now loaded from Blog module (src/Modules/Optional/Blog/routes.php)

    // ===== MONITORING ROUTES =====
    (require __DIR__ . '/routes/monitoring.php')($app);

    // ===== DOCUMENTATION ROUTES =====
    (require __DIR__ . '/routes/docs.php')($app);
};
