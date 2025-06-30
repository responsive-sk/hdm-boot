<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Actions\Web;

use HdmBoot\Modules\Core\Session\Services\SessionService;
use HdmBoot\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
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
        // Check if user is already logged in
        $isLoggedIn = $this->sessionService->isLoggedIn();

        if ($isLoggedIn) {
            $this->sessionService->setFlash('info', 'You are already logged in.');

            // Redirect to profile or home
            return $response
                ->withHeader('Location', '/profile')
                ->withStatus(302);
        }

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
