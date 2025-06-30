<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Infrastructure\Actions;

use HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Health Check Action.
 *
 * Provides health check endpoint for monitoring and load balancers.
 */
final class HealthCheckAction
{
    public function __construct(
        private readonly HealthCheckManager $healthCheckManager,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle health check request.
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $checkName = $queryParams['check'] ?? null;

        try {
            if ($checkName !== null && is_string($checkName)) {
                // Run specific health check
                return $this->handleSpecificCheck($checkName);
            }

            // Run all health checks
            return $this->handleAllChecks();
        } catch (\Exception $e) {
            $this->logger->error('Health check endpoint failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createErrorResponse('Health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle request for all health checks.
     */
    private function handleAllChecks(): ResponseInterface
    {
        $report = $this->healthCheckManager->checkHealth();

        $response = $this->responseFactory->createResponse($report->getHttpStatusCode());
        $response = $response->withHeader('Content-Type', 'application/json');
        $response = $response->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

        $response->getBody()->write(json_encode($report->toArray(), JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Handle request for specific health check.
     */
    private function handleSpecificCheck(string $checkName): ResponseInterface
    {
        $result = $this->healthCheckManager->checkSpecific($checkName);

        if ($result === null) {
            return $this->createNotFoundResponse("Health check '{$checkName}' not found");
        }

        $statusCode = $result->status->getHttpStatusCode();
        $response = $this->responseFactory->createResponse($statusCode);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response = $response->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

        $response->getBody()->write(json_encode($result->toArray(), JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Create error response.
     */
    private function createErrorResponse(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(500);
        $response = $response->withHeader('Content-Type', 'application/json');

        $errorData = [
            'status'    => 'error',
            'message'   => $message,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
        ];

        $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Create not found response.
     */
    private function createNotFoundResponse(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(404);
        $response = $response->withHeader('Content-Type', 'application/json');

        $errorData = [
            'status'           => 'not_found',
            'message'          => $message,
            'available_checks' => $this->healthCheckManager->getRegisteredChecks(),
            'timestamp'        => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
        ];

        $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

        return $response;
    }
}
