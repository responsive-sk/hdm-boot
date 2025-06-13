<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Infrastructure\Middleware;

use MvaBootstrap\Modules\Core\User\Services\UserService;
use Odan\Session\SessionInterface as OdanSession;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * User Authentication Middleware.
 *
 * Checks if user is logged in and has valid session.
 * Inspired by samuelgfeller/slim-example-project.
 */
final class UserAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly OdanSession $session,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Check user authentication and redirect to login if needed.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Ensure session is started for user authentication
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        // Debug: Log session state in middleware
        error_log('UserAuthenticationMiddleware: session_id=' . $this->session->getId());

        // Check if user is logged in
        $userIdString = $this->session->get('user_id');
        $lastActivity = $this->session->get('last_activity');

        error_log(sprintf(
            'UserAuthenticationMiddleware: user_id=%s, last_activity=%s',
            $userIdString ?? 'NULL',
            $lastActivity ?? 'NULL'
        ));

        if (is_string($userIdString) && !empty($userIdString)) {
            try {
                // Verify user exists and is active
                $user = $this->userService->getUserById($userIdString);

                if ($user && isset($user['status']) && $user['status'] === 'active') {
                    // User is authenticated and active, continue
                    return $handler->handle($request);
                }

                // User not found or inactive, logout
                $this->logger->warning('User authentication failed - user not found or inactive', [
                    'user_id' => $userIdString,
                    'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                // Error checking user, logout for security
                $this->logger->error('User authentication error', [
                    'user_id' => $userIdString,
                    'error'   => $e->getMessage(),
                    'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }

            // Clear invalid session
            $this->session->destroy();
            if (!$this->session->isStarted()) {
                $this->session->start();
            }
            $this->session->regenerateId();
        }

        // User not authenticated, redirect to login
        $response = $this->responseFactory->createResponse();

        // Add flash message
        $this->session->getFlash()->add('info', 'Please login to access this page.');

        // Check if it's JSON request
        $contentType = $request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            // Return JSON response for API requests
            $jsonData = json_encode([
                'error'                => 'Authentication required',
                'login_url'            => '/login',
                'redirect_after_login' => $request->getUri()->getPath(),
            ]);

            if ($jsonData === false) {
                $jsonData = '{"error": "Authentication required"}';
            }

            $response->getBody()->write($jsonData);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Build redirect URL with query params
        $redirectPath = $request->getUri()->getPath();
        $queryParams = $request->getQueryParams();

        if ($redirectPath !== '/login') {
            $queryParams['redirect'] = $redirectPath;
        }

        $loginUrl = '/login';
        if (!empty($queryParams)) {
            $loginUrl .= '?' . http_build_query($queryParams);
        }

        // Redirect to login page
        return $response
            ->withHeader('Location', $loginUrl)
            ->withStatus(302);
    }
}
