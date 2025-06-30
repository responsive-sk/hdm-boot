<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Middleware;

use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Exceptions\ProblemDetailsException;
use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Error Handler Middleware.
 *
 * Centralized error handling middleware that catches exceptions
 * and converts them to standardized RFC 7807 Problem Details responses.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ErrorResponseHandler $errorResponseHandler,
        private readonly LoggerInterface $logger,
        private readonly bool $displayErrorDetails = false
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (ProblemDetailsException $e) {
            // Handle known application exceptions with Problem Details
            return $this->handleProblemDetailsException($e, $request);
        } catch (\InvalidArgumentException $e) {
            // Convert InvalidArgumentException to validation error
            return $this->handleValidationException($e, $request);
        } catch (\Throwable $e) {
            // Handle unexpected exceptions
            return $this->handleUnexpectedException($e, $request);
        }
    }

    /**
     * Handle ProblemDetailsException.
     */
    private function handleProblemDetailsException(
        ProblemDetailsException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $problemDetails = $exception->getProblemDetails();

        // Log based on error type
        if ($exception->isClientError()) {
            $this->logger->warning('Client error occurred', [
                'exception'      => $exception->toArray(),
                'request_uri'    => (string) $request->getUri(),
                'request_method' => $request->getMethod(),
            ]);
        } else {
            $this->logger->error('Server error occurred', [
                'exception'      => $exception->toArray(),
                'request_uri'    => (string) $request->getUri(),
                'request_method' => $request->getMethod(),
                'trace'          => $exception->getTraceAsString(),
            ]);
        }

        return $this->errorResponseHandler->createProblemDetailsResponse(
            $problemDetails,
            $request
        );
    }

    /**
     * Handle InvalidArgumentException as validation error.
     */
    private function handleValidationException(
        \InvalidArgumentException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $this->logger->warning('Validation error occurred', [
            'message'        => $exception->getMessage(),
            'request_uri'    => (string) $request->getUri(),
            'request_method' => $request->getMethod(),
        ]);

        return $this->errorResponseHandler->createValidationErrorResponse(
            $exception->getMessage(),
            $request
        );
    }

    /**
     * Handle unexpected exceptions.
     */
    private function handleUnexpectedException(
        \Throwable $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        // Generate trace ID for tracking
        $traceId = uniqid('error_', true);

        $this->logger->error('Unexpected error occurred', [
            'trace_id'        => $traceId,
            'exception_class' => get_class($exception),
            'message'         => $exception->getMessage(),
            'file'            => $exception->getFile(),
            'line'            => $exception->getLine(),
            'request_uri'     => (string) $request->getUri(),
            'request_method'  => $request->getMethod(),
            'trace'           => $exception->getTraceAsString(),
        ]);

        // Determine error detail based on display settings
        $detail = $this->displayErrorDetails
            ? $exception->getMessage()
            : 'An internal server error occurred';

        return $this->errorResponseHandler->createInternalServerErrorResponse(
            $detail,
            $request,
            $traceId
        );
    }
}
