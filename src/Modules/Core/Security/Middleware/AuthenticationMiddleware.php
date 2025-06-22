<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Middleware;

use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Authentication Middleware
 *
 * Validates JWT tokens and sets user/token attributes on request.
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly bool $required = true
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            // Get Authorization header
            $authHeader = $request->getHeaderLine('Authorization');

            if (empty($authHeader)) {
                return $this->handleMissingAuth($request, $handler);
            }

            // Validate token
            $user = $this->authenticationService->validateAuthorizationHeader($authHeader);

            if (!$user) {
                return $this->handleInvalidAuth($request, $handler);
            }

            // Add user and token to request attributes
            $request = $request
                ->withAttribute('user', $user)
                ->withAttribute('authenticated', true);

            // Extract token from header for token info
            if (str_starts_with($authHeader, 'Bearer ')) {
                $tokenString = substr($authHeader, 7);
                $token = \HdmBoot\Modules\Core\Security\Domain\ValueObjects\JwtToken::fromString($tokenString);
                $request = $request->withAttribute('token', $token);
            }

            $this->logger->debug('User authenticated successfully', [
                'user_id' => $user['id'] ?? 'unknown',
                'email' => $user['email'] ?? 'unknown',
            ]);

            return $handler->handle($request);
        } catch (\Exception $e) {
            $this->logger->error('Authentication middleware error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleAuthError($request, $handler);
        }
    }

    private function handleMissingAuth(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->required) {
            // Optional authentication - continue without user
            return $handler->handle($request->withAttribute('authenticated', false));
        }

        return $this->createUnauthorizedResponse('Missing authorization header');
    }

    private function handleInvalidAuth(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->required) {
            // Optional authentication - continue without user
            return $handler->handle($request->withAttribute('authenticated', false));
        }

        return $this->createUnauthorizedResponse('Invalid or expired token');
    }

    private function handleAuthError(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->required) {
            // Optional authentication - continue without user
            return $handler->handle($request->withAttribute('authenticated', false));
        }

        return $this->createUnauthorizedResponse('Authentication error');
    }

    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        $errorData = [
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
        ];

        $jsonResponse = json_encode($errorData);
        if ($jsonResponse === false) {
            $jsonResponse = '{"success":false,"message":"Unauthorized"}';
        }

        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write($jsonResponse);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create middleware instance that allows optional authentication.
     */
    public static function optional(
        AuthenticationService $authenticationService,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ): self {
        return new self($authenticationService, $responseFactory, $logger, false);
    }

    /**
     * Create middleware instance that requires authentication.
     */
    public static function required(
        AuthenticationService $authenticationService,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ): self {
        return new self($authenticationService, $responseFactory, $logger, true);
    }
}
