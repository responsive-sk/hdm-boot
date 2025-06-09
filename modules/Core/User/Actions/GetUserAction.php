<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Actions;

use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Get User Action.
 *
 * Handles GET /api/users/{id} - get single user by ID.
 */
final class GetUserAction
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $userId = $args['id'] ?? '';

        if (!is_string($userId) || empty($userId)) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'User ID is required',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/GetUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'USER_NOT_FOUND',
                        'message' => 'User not found',
                    ],
                ];

                $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/GetUserAction.php");

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $data = [
                'success' => true,
                'data'    => $user->toArray(),
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: "modules/Core/User/Actions/GetUserAction.php");

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INTERNAL_ERROR',
                    'message' => 'Failed to retrieve user',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/GetUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
