<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Application\Queries;

use HdmBoot\Modules\Core\CQRS\Infrastructure\Queries\QueryInterface;

/**
 * Find User By Email Query.
 *
 * Query to find user by email address.
 */
final readonly class FindUserByEmailQuery implements QueryInterface
{
    public function __construct(
        public string $queryId,
        public string $email,
        public bool $includePassword = false,
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
            queryId: $data['query_id'] ?? uniqid('find_user_by_email_', true),
            email: (string) ($data['email'] ?? ''),
            includePassword: (bool) ($data['include_password'] ?? false),
            requestedBy: isset($data['requested_by']) ? (string) $data['requested_by'] : null
        );
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getQueryName(): string
    {
        return 'find_user_by_email';
    }

    /**
     * Validate query parameters.
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->email))) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        return $errors;
    }

    public function toLogArray(): array
    {
        return [
            'query_id'         => $this->queryId,
            'query_name'       => $this->getQueryName(),
            'email'            => $this->email,
            'include_password' => $this->includePassword,
            'requested_by'     => $this->requestedBy,
        ];
    }
}
