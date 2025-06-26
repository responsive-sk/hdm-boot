<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Actions\Api;

use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListUsersAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        // Parse filters from query parameters
        $filters = [
            'role'   => $queryParams['role'] ?? null,
            'status' => $queryParams['status'] ?? null,
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Get users with filters
        $users = $this->userRepository->findAll($filters);

        // Format response
        $data = [
            'success' => true,
            'data'    => $users,
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
