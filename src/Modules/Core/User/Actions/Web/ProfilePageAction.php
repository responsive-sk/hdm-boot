<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Actions\Web;

use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use ResponsiveSk\Slim4Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Profile Page Action.
 *
 * Displays user profile page (protected route).
 */
final class ProfilePageAction
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly SessionInterface $session,
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // UserAuthenticationMiddleware already checked authentication
        // So we can assume user is logged in here

        $userIdString = $this->session->get('user_id');

        // This should never happen due to middleware, but safety check
        if (!is_string($userIdString) || empty($userIdString)) {
            throw new \RuntimeException('User ID not found in session after authentication middleware');
        }

        try {
            // Get full user data from database
            $user = $this->userService->getUserById($userIdString);

            // Ensure user was found
            if ($user === null) {
                throw new \RuntimeException('User not found in database');
            }

            // Convert user object to array if needed
            if (is_object($user)) {
                $user = method_exists($user, 'toArray') ? $user->toArray() : (array)$user;
            }
            // Always add user_id for template simplicity
            $user['user_id'] = $userIdString;

            // Get session info for debugging
            $sessionInfo = [
                'session_id'      => session_id(),
                'user_id'         => $userIdString,
                'user_data'       => $this->session->get('user_data'),
                'session_started' => $this->session->isStarted(),
            ];

            // Log successful profile access
            $this->logger->info('Profile page accessed successfully', [
                'user_id' => $userIdString,
                'email' => $user['email'] ?? 'unknown',
                'session_id' => session_id(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // Render user profile template
            $result = $this->templateRenderer->render(
                $response,
                'user/profile.php',
                [
                    'title'       => 'Profile',
                    'user'        => $user,
                    'sessionInfo' => $sessionInfo,
                ]
            );

            return $result;
        } catch (\Exception $e) {
            // Log profile access error
            $this->logger->error('Profile page access failed', [
                'user_id' => $userIdString ?? 'unknown',
                'error' => $e->getMessage(),
                'session_id' => session_id(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // User not found or other error
            $this->session->getFlash()->add('error', 'Unable to load profile. Please try logging in again.');
            $this->session->destroy();

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }
}
