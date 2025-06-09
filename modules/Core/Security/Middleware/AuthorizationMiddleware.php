<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Middleware;

use MvaBootstrap\Modules\Core\Security\Services\AuthorizationService;
use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authorization Middleware.
 *
 * Checks if authenticated user has required permissions.
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly string $requiredPermission
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get authenticated user from request attributes
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->createForbiddenResponse('User not authenticated');
        }

        // Check permission
        if (!$this->authorizationService->hasPermission($user, $this->requiredPermission)) {
            return $this->createForbiddenResponse(
                "Access denied. Required permission: {$this->requiredPermission}"
            );
        }

        return $handler->handle($request);
    }

    /**
     * Create forbidden response.
     */
    private function createForbiddenResponse(string $message): ResponseInterface
    {
        $response = new \Slim\Psr7\Response();

        $errorData = [
            'success' => false,
            'error'   => [
                'code'                => 'FORBIDDEN',
                'message'             => $message,
                'required_permission' => $this->requiredPermission,
            ],
        ];

        $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Middleware/AuthorizationMiddleware.php");

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
