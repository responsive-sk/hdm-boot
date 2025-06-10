<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions\Web;

use MvaBootstrap\Modules\Core\Security\Services\CsrfService;
use Odan\Session\SessionInterface as OdanSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Logout Action.
 *
 * Handles user logout with CSRF protection.
 */
final class LogoutAction
{
    public function __construct(
        private readonly OdanSession $session,
        private readonly CsrfService $csrfService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = $request->getMethod();
        $data = (array) $request->getParsedBody();

        try {
            // Only validate CSRF token for POST requests (like samuelgfeller GET logout)
            if ($method === 'POST') {
                $this->csrfService->validateFromRequest($data, 'logout');
            }

            // Get user data before logout for logging
            $userData = $this->session->get('user_data');

            // Log logout
            if ($userData) {
                $this->logger->info('User logged out', [
                    'email' => $userData['email'] ?? 'unknown',
                    'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }

            // Destroy session
            $this->session->destroy();

            // Start new session for flash message
            $this->session->start();
            $this->session->getFlash()->add('success', 'You have been logged out successfully.');

            // Redirect to home
            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        } catch (\Exception $e) {
            // CSRF validation failed or other error
            $this->logger->warning('Logout failed', [
                'message' => $e->getMessage(),
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // Still logout for security
            $this->session->destroy();

            // Start new session for flash message
            $this->session->start();
            $this->session->getFlash()->add('error', 'Logout failed due to security error.');

            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        }
    }
}
