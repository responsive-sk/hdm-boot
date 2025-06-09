<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Middleware;

use MvaBootstrap\Modules\Core\Security\Exception\AuthenticationException;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authentication Middleware.
 *
 * Validates JWT tokens and adds user information to request attributes.
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->createUnauthorizedResponse('Missing Authorization header');
        }

        try {
            // Validate token
            $token = $this->authenticationService->validateAuthorizationHeader($authHeader);

            // Get user from token
            $user = $this->authenticationService->getUserFromToken($token);

            if (!$user) {
                return $this->createUnauthorizedResponse('User not found');
            }

            if (!$user->isActive()) {
                return $this->createUnauthorizedResponse('User account is not active');
            }

            // Add user and token to request attributes
            $request = $request
                ->withAttribute('user', $user)
                ->withAttribute('token', $token)
                ->withAttribute('authenticated', true);

            return $handler->handle($request);
        } catch (AuthenticationException $e) {
            return $this->createUnauthorizedResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->createUnauthorizedResponse('Authentication failed');
        }
    }

    /**
     * Create unauthorized response.
     */
    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        $response = new \Slim\Psr7\Response();

        $errorData = [
            'success' => false,
            'error'   => [
                'code'    => 'UNAUTHORIZED',
                'message' => $message,
            ],
        ];

        $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Middleware/AuthenticationMiddleware.php");

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
