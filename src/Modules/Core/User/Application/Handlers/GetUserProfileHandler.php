<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Application\Handlers;

use InvalidArgumentException;
use HdmBoot\SharedKernel\CQRS\Handlers\QueryHandlerInterface;
use HdmBoot\SharedKernel\CQRS\Queries\QueryInterface;
use HdmBoot\Modules\Core\User\Application\Queries\GetUserProfileQuery;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Get User Profile Query Handler.
 *
 * Handles user profile retrieval queries with proper data filtering
 * based on access permissions.
 */
final class GetUserProfileHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle the query and return user profile data.
     *
     * @return array<string, mixed>|null
     */
    public function handle(QueryInterface $query): ?array
    {
        if (!$query instanceof GetUserProfileQuery) {
            throw new InvalidArgumentException(
                'Expected GetUserProfileQuery, got ' . get_class($query)
            );
        }

        $this->logger->info('Handling get user profile query', $query->toLogArray());

        try {
            // Validate query
            $validationErrors = $query->validate();
            if (!empty($validationErrors)) {
                throw new InvalidArgumentException(
                    'Query validation failed: ' . implode(', ', $validationErrors)
                );
            }

            // Retrieve user data
            $userData = $this->userRepository->findById($query->userId);

            if (!$userData) {
                $this->logger->warning('User not found', [
                    'query_id' => $query->getQueryId(),
                    'user_id'  => $query->userId,
                ]);

                return null;
            }

            // Filter data based on access permissions
            $profileData = $this->filterUserData($userData, $query);

            $this->logger->info('User profile retrieved successfully', [
                'query_id'             => $query->getQueryId(),
                'user_id'              => $query->userId,
                'include_private_data' => $query->includePrivateData,
            ]);

            return $profileData;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user profile', [
                'query_id'   => $query->getQueryId(),
                'error'      => $e->getMessage(),
                'query_data' => $query->toLogArray(),
            ]);

            throw $e;
        }
    }

    public function getSupportedQueryClass(): string
    {
        return GetUserProfileQuery::class;
    }

    /**
     * Filter user data based on access permissions.
     *
     * @param array<string, mixed> $userData
     *
     * @return array<string, mixed>
     */
    private function filterUserData(array $userData, GetUserProfileQuery $query): array
    {
        // Base public data
        $profileData = [
            'id'         => $userData['id'],
            'name'       => $userData['name'],
            'email'      => $userData['email'],
            'role'       => $userData['role'],
            'status'     => $userData['status'],
            'created_at' => $userData['created_at'],
        ];

        // Include private data if requested and authorized
        if ($query->includePrivateData) {
            $profileData['updated_at'] = $userData['updated_at'] ?? null;
            $profileData['last_login_at'] = $userData['last_login_at'] ?? null;

            // Note: password is never included in profile data
        }

        return $profileData;
    }
}
