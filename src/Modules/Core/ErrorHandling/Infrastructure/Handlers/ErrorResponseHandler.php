<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Handlers;

use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Error Response Handler.
 *
 * Creates standardized HTTP responses for errors using RFC 7807 Problem Details.
 */
final class ErrorResponseHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * Create Problem Details response.
     */
    public function createProblemDetailsResponse(
        ProblemDetails $problemDetails,
        ServerRequestInterface $request
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($problemDetails->getStatus());

        // Set instance if not provided
        if ($problemDetails->instance === null) {
            $problemDetails = ProblemDetails::custom(
                type: $problemDetails->type,
                title: $problemDetails->title,
                status: $problemDetails->status,
                detail: $problemDetails->detail,
                instance: $this->getRequestInstance($request),
                extensions: $problemDetails->extensions
            );
        }

        // Set Content-Type header for Problem Details
        $response = $response->withHeader('Content-Type', 'application/problem+json');

        // Write JSON response
        $response->getBody()->write($problemDetails->toJson());

        return $response;
    }

    /**
     * Create validation error response.
     */
    public function createValidationErrorResponse(
        string $detail,
        ServerRequestInterface $request,
        array $validationErrors = []
    ): ResponseInterface {
        $problemDetails = ProblemDetails::validationError(
            detail: $detail,
            validationErrors: $validationErrors,
            instance: $this->getRequestInstance($request)
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Create authentication error response.
     */
    public function createAuthenticationErrorResponse(
        string $detail,
        ServerRequestInterface $request
    ): ResponseInterface {
        $problemDetails = ProblemDetails::authenticationError(
            detail: $detail,
            instance: $this->getRequestInstance($request)
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Create authorization error response.
     */
    public function createAuthorizationErrorResponse(
        string $detail,
        ServerRequestInterface $request
    ): ResponseInterface {
        $problemDetails = ProblemDetails::authorizationError(
            detail: $detail,
            instance: $this->getRequestInstance($request)
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Create not found error response.
     */
    public function createNotFoundErrorResponse(
        string $detail,
        ServerRequestInterface $request
    ): ResponseInterface {
        $problemDetails = ProblemDetails::notFoundError(
            detail: $detail,
            instance: $this->getRequestInstance($request)
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Create rate limit error response.
     */
    public function createRateLimitErrorResponse(
        string $detail,
        ServerRequestInterface $request,
        ?int $retryAfter = null
    ): ResponseInterface {
        $problemDetails = ProblemDetails::rateLimitError(
            detail: $detail,
            instance: $this->getRequestInstance($request),
            retryAfter: $retryAfter
        );

        $response = $this->createProblemDetailsResponse($problemDetails, $request);

        // Add Retry-After header if provided
        if ($retryAfter !== null) {
            $response = $response->withHeader('Retry-After', (string) $retryAfter);
        }

        return $response;
    }

    /**
     * Create internal server error response.
     */
    public function createInternalServerErrorResponse(
        string $detail,
        ServerRequestInterface $request,
        ?string $traceId = null
    ): ResponseInterface {
        $problemDetails = ProblemDetails::internalServerError(
            detail: $detail,
            instance: $this->getRequestInstance($request),
            traceId: $traceId
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Create custom error response.
     */
    public function createCustomErrorResponse(
        string $type,
        string $title,
        int $status,
        string $detail,
        ServerRequestInterface $request,
        array $extensions = []
    ): ResponseInterface {
        $problemDetails = ProblemDetails::custom(
            type: $type,
            title: $title,
            status: $status,
            detail: $detail,
            instance: $this->getRequestInstance($request),
            extensions: $extensions
        );

        return $this->createProblemDetailsResponse($problemDetails, $request);
    }

    /**
     * Get request instance for Problem Details.
     */
    private function getRequestInstance(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();

        return $query ? "{$path}?{$query}" : $path;
    }
}
