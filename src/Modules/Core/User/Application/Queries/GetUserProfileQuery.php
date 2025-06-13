<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Application\Queries;

use MvaBootstrap\Modules\Core\CQRS\Infrastructure\Queries\QueryInterface;

/**
 * Get User Profile Query.
 *
 * Query to retrieve user profile information.
 */
final readonly class GetUserProfileQuery implements QueryInterface
{
    public function __construct(
        public string $queryId,
        public string $userId,
        public bool $includePrivateData = false,
        public ?string $requestedBy = null
    ) {
    }

    /**
     * Create query from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            queryId: $data['query_id'] ?? uniqid('get_user_profile_', true),
            userId: (string) ($data['user_id'] ?? ''),
            includePrivateData: (bool) ($data['include_private_data'] ?? false),
            requestedBy: isset($data['requested_by']) ? (string) $data['requested_by'] : null
        );
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getQueryName(): string
    {
        return 'get_user_profile';
    }

    /**
     * Validate query parameters.
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->userId))) {
            $errors[] = 'User ID is required';
        }

        return $errors;
    }

    public function toLogArray(): array
    {
        return [
            'query_id'             => $this->queryId,
            'query_name'           => $this->getQueryName(),
            'user_id'              => $this->userId,
            'include_private_data' => $this->includePrivateData,
            'requested_by'         => $this->requestedBy,
        ];
    }
}
