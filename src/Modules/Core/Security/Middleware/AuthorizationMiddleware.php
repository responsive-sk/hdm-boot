<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Middleware;

use HdmBoot\Modules\Core\Security\Services\AuthorizationService;
use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\SharedKernel\Contracts\MiddlewareInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Authorization Middleware with Proper Dependency Injection.
 *
 * Handles user authorization using injected dependencies instead of direct instantiation.
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string> $requiredPermissions
     */
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly UserService $userService,
        private readonly SessionInterface $session,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly array $requiredPermissions = []
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            // Get user from session with safe type checking
            $userIdValue = $this->session->get('user_id');
            $userId = is_string($userIdValue) ? $userIdValue : null;

            if (!$userId) {
                $this->logger->warning('Authorization failed: No user in session');

                return $this->createUnauthorizedResponse();
            }

            // Get user data
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                $this->logger->warning('Authorization failed: User not found', ['user_id' => $userId]);

                return $this->createUnauthorizedResponse();
            }

            // Check if user is active
            if (!isset($user['status']) || $user['status'] !== 'active') {
                $this->logger->warning('Authorization failed: User not active', ['user_id' => $userId]);

                return $this->createUnauthorizedResponse();
            }

            // Check required permissions
            if (!empty($this->requiredPermissions)) {
                foreach ($this->requiredPermissions as $permission) {
                    if (!$this->authorizationService->hasPermission($user, $permission)) {
                        $this->logger->warning('Authorization failed: Missing permission', [
                            'user_id'    => $userId,
                            'permission' => $permission,
                        ]);

                        return $this->createForbiddenResponse();
                    }
                }
            }

            // Add user to request attributes for downstream use
            $request = $request->withAttribute('user', $user);

            return $handler->handle($request);
        } catch (\Exception $e) {
            $this->logger->error('Authorization middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createUnauthorizedResponse();
        }
    }

    /**
     * Create middleware instance with specific permissions.
     *
     * @param array<string> $permissions
     */
    public static function withPermissions(array $permissions): string
    {
        // This will be resolved by the container with the permissions
        return static::class . ':' . implode(',', $permissions);
    }

    private function createUnauthorizedResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);
        $jsonResponse = json_encode([
            'error'   => 'Unauthorized',
            'message' => 'Authentication required',
        ]);

        if ($jsonResponse !== false) {
            $response->getBody()->write($jsonResponse);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function createForbiddenResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(403);
        $jsonResponse = json_encode([
            'error'   => 'Forbidden',
            'message' => 'Insufficient permissions',
        ]);

        if ($jsonResponse !== false) {
            $response->getBody()->write($jsonResponse);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
