<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Exceptions;

use Exception;
use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * Base exception that carries RFC 7807 Problem Details.
 *
 * All application exceptions should extend this class to ensure
 * consistent error handling and API responses.
 */
abstract class ProblemDetailsException extends Exception
{
    protected ProblemDetails $problemDetails;

    public function __construct(
        ProblemDetails $problemDetails,
        ?\Throwable $previous = null
    ) {
        $this->problemDetails = $problemDetails;

        parent::__construct(
            $problemDetails->detail ?? $problemDetails->title,
            $problemDetails->status,
            $previous
        );
    }

    /**
     * Get the problem details.
     */
    public function getProblemDetails(): ProblemDetails
    {
        return $this->problemDetails;
    }

    /**
     * Get HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->problemDetails->status;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->problemDetails->isClientError();
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->problemDetails->isServerError();
    }

    /**
     * Convert to array for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exception_class' => static::class,
            'message'         => $this->getMessage(),
            'code'            => $this->getCode(),
            'file'            => $this->getFile(),
            'line'            => $this->getLine(),
            'problem_details' => $this->problemDetails->toArray(),
        ];
    }
}
