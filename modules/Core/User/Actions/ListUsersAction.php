<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Actions;

use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * List Users Action.
 *
 * Handles GET /api/users - list users with pagination and filters.
 */
final class ListUsersAction
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();

        // Pagination parameters
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int)($queryParams['limit'] ?? 20)));

        // Filter parameters
        $filters = [];
        if (!empty($queryParams['role'])) {
            $filters['role'] = $queryParams['role'];
        }
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }

        try {
            $result = $this->userService->getUsersWithPagination($page, $limit, $filters);

            $data = [
                'success' => true,
                'data' => array_map(fn($user) => $user->toArray(), $result['users']),
                'pagination' => [
                    'current_page' => $result['page'],
                    'per_page' => $result['limit'],
                    'total' => $result['total'],
                    'total_pages' => $result['total_pages'],
                    'has_next' => $result['page'] < $result['total_pages'],
                    'has_prev' => $result['page'] > 1,
                ],
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Failed to retrieve users',
                ],
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
