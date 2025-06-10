<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Actions\Web;

use MvaBootstrap\Modules\Core\User\Services\UserService;
use Odan\Session\SessionInterface as OdanSession;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Profile Page Action.
 *
 * Displays user profile page (protected route).
 */
final class ProfilePageAction
{
    public function __construct(
        private readonly PhpRenderer $templateRenderer,
        private readonly OdanSession $session,
        private readonly UserService $userService
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

            // Get session info for debugging
            $sessionInfo = [
                'session_id' => session_id(),
                'user_id' => $userIdString,
                'user_data' => $this->session->get('user_data'),
                'session_started' => $this->session->isStarted(),
            ];

            // Ensure user was found
            if ($user === null) {
                throw new \RuntimeException('User not found in database');
            }

            // Render user profile template

            $result = $this->templateRenderer->render(
                $response,
                'user/profile_simple.php',
                [
                    'title' => 'Profile',
                    'user' => $user,
                    'sessionInfo' => $sessionInfo,
                ]
            );

            return $result;
        } catch (\Exception $e) {
            // User not found or other error
            $this->session->getFlash()->add('error', 'Unable to load profile. Please try logging in again.');
            $this->session->destroy();

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }
}
