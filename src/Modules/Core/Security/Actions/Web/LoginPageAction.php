<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions\Web;

use MvaBootstrap\Modules\Core\Session\Services\SessionService;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Login Page Action.
 *
 * Displays login form with CSRF protection.
 */
final class LoginPageAction
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly SessionService $sessionService
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Debug: Check session state
        error_log('LoginPageAction: Checking if user is logged in...');
        error_log('LoginPageAction: SESSION_NAME from env = ' . ($_ENV['SESSION_NAME'] ?? 'NOT_SET'));
        error_log('LoginPageAction: Current session name = ' . session_name());

        // Check if user is already logged in
        $isLoggedIn = $this->sessionService->isLoggedIn();

        error_log('LoginPageAction: isLoggedIn() result = ' . ($isLoggedIn ? 'TRUE' : 'FALSE'));

        if ($isLoggedIn) {
            error_log('LoginPageAction: User is logged in, redirecting to profile');
            $this->sessionService->setFlash('info', 'You are already logged in.');

            // Redirect to profile or home
            return $response
                ->withHeader('Location', '/profile')
                ->withStatus(302);
        }

        error_log('LoginPageAction: User not logged in, showing login form');

        return $this->templateRenderer->render(
            $response,
            'auth/login.php',
            [
                'title'       => 'Login',
                'queryParams' => $request->getQueryParams(),
            ]
        );
    }
}
