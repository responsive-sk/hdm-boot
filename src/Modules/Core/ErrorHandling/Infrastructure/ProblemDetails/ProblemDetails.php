<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails;

use JsonSerializable;

/**
 * RFC 7807 Problem Details for HTTP APIs.
 *
 * Provides a standardized format for API error responses.
 *
 * @see https://tools.ietf.org/html/rfc7807
 */
final readonly class ProblemDetails implements JsonSerializable
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $extensions = []
    ) {
    }

    /**
     * Create a validation error problem.
     *
     * @param array<string, string|array<string>> $validationErrors
     */
    public static function validationError(
        string $detail,
        array $validationErrors = [],
        ?string $instance = null
    ): self {
        return new self(
            type: 'https://httpstatuses.com/400',
            title: 'Validation Error',
            status: 400,
            detail: $detail,
            instance: $instance,
            extensions: $validationErrors ? ['validation_errors' => $validationErrors] : []
        );
    }

    /**
     * Create an authentication error problem.
     */
    public static function authenticationError(
        string $detail = 'Authentication failed',
        ?string $instance = null
    ): self {
        return new self(
            type: 'https://httpstatuses.com/401',
            title: 'Authentication Error',
            status: 401,
            detail: $detail,
            instance: $instance
        );
    }

    /**
     * Create an authorization error problem.
     */
    public static function authorizationError(
        string $detail = 'Access denied',
        ?string $instance = null
    ): self {
        return new self(
            type: 'https://httpstatuses.com/403',
            title: 'Authorization Error',
            status: 403,
            detail: $detail,
            instance: $instance
        );
    }

    /**
     * Create a not found error problem.
     */
    public static function notFoundError(
        string $detail = 'Resource not found',
        ?string $instance = null
    ): self {
        return new self(
            type: 'https://httpstatuses.com/404',
            title: 'Not Found',
            status: 404,
            detail: $detail,
            instance: $instance
        );
    }

    /**
     * Create a conflict error problem.
     */
    public static function conflictError(
        string $detail = 'Resource conflict',
        ?string $instance = null
    ): self {
        return new self(
            type: 'https://httpstatuses.com/409',
            title: 'Conflict',
            status: 409,
            detail: $detail,
            instance: $instance
        );
    }

    /**
     * Create a rate limit error problem.
     */
    public static function rateLimitError(
        string $detail = 'Rate limit exceeded',
        ?string $instance = null,
        ?int $retryAfter = null
    ): self {
        $extensions = $retryAfter ? ['retry_after' => $retryAfter] : [];

        return new self(
            type: 'https://httpstatuses.com/429',
            title: 'Too Many Requests',
            status: 429,
            detail: $detail,
            instance: $instance,
            extensions: $extensions
        );
    }

    /**
     * Create an internal server error problem.
     */
    public static function internalServerError(
        string $detail = 'An internal server error occurred',
        ?string $instance = null,
        ?string $traceId = null
    ): self {
        $extensions = $traceId ? ['trace_id' => $traceId] : [];

        return new self(
            type: 'https://httpstatuses.com/500',
            title: 'Internal Server Error',
            status: 500,
            detail: $detail,
            instance: $instance,
            extensions: $extensions
        );
    }

    /**
     * Create a custom problem.
     *
     * @param array<string, mixed> $extensions
     */
    public static function custom(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = []
    ): self {
        return new self(
            type: $type,
            title: $title,
            status: $status,
            detail: $detail,
            instance: $instance,
            extensions: $extensions
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type'   => $this->type,
            'title'  => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== null) {
            $data['detail'] = $this->detail;
        }

        if ($this->instance !== null) {
            $data['instance'] = $this->instance;
        }

        // Add extensions
        foreach ($this->extensions as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Get HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->status >= 500;
    }
}
