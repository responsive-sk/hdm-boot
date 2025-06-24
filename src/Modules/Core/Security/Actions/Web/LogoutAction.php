<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Actions\Web;

use HdmBoot\Modules\Core\Session\Services\CsrfService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Logout Action.
 *
 * Handles user logout with CSRF protection.
 */
final class LogoutAction
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly CsrfService $csrfService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = $request->getMethod();
        $parsedBody = $request->getParsedBody();
        /** @var array<string, mixed> $data */
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            // Only validate CSRF token for POST requests (like samuelgfeller GET logout)
            if ($method === 'POST') {
                $this->csrfService->validateFromRequest($data, 'logout');
            }

            // Get user data before logout for logging
            $userData = $this->session->get('user_data');

            // Log logout
            if (is_array($userData)) {
                $this->logger->info('User logged out', [
                    'email' => $userData['email'] ?? 'unknown',
                    'ip'    => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }

            // Set flash message before destroying session
            $this->session->flash('success', 'You have been logged out successfully.');

            // Destroy session (flash message will persist to next session)
            $this->session->destroy();

            // Redirect to home
            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        } catch (\Exception $e) {
            // CSRF validation failed or other error
            $this->logger->warning('Logout failed', [
                'message' => $e->getMessage(),
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // Set flash message before destroying session
            $this->session->flash('error', 'Logout failed due to security error.');

            // Still logout for security
            $this->session->destroy();

            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        }
    }
}
