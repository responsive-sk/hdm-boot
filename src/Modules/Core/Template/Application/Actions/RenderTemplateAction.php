<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Template\Application\Actions;

use MvaBootstrap\Modules\Core\Template\Application\DTOs\RenderTemplateRequest;
use MvaBootstrap\Modules\Core\Template\Domain\Services\TemplateService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Render Template Action.
 *
 * HTTP adapter for template rendering operations.
 */
final class RenderTemplateAction
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle template rendering request.
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('Processing template render request', [
                'method' => $request->getMethod(),
                'uri'    => (string) $request->getUri(),
            ]);

            // Parse request data
            $requestData = $this->parseRequestData($request);
            $renderRequest = RenderTemplateRequest::fromArray($requestData);

            // Render template
            $content = $this->templateService->render(
                $renderRequest->template,
                $renderRequest->data
            );

            // Create response
            $response = $this->responseFactory->createResponse(200);
            $response->getBody()->write($content);

            // Set content type
            $contentType = $renderRequest->contentType ?? 'text/html';
            $response = $response->withHeader('Content-Type', $contentType);

            $this->logger->info('Template rendered successfully', [
                'template'       => $renderRequest->template,
                'content_length' => strlen($content),
                'content_type'   => $contentType,
            ]);

            return $response;
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid template render request', [
                'error' => $e->getMessage(),
            ]);

            return $this->createErrorResponse(400, 'Bad Request', $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->logger->error('Template rendering failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return $this->createErrorResponse(500, 'Internal Server Error', 'Template rendering failed');
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during template rendering', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return $this->createErrorResponse(500, 'Internal Server Error', 'An unexpected error occurred');
        }
    }

    /**
     * Parse request data.
     *
     * @return array<string, mixed>
     */
    private function parseRequestData(ServerRequestInterface $request): array
    {
        $method = $request->getMethod();

        if ($method === 'GET') {
            $queryParams = $request->getQueryParams();
            /** @var array<string, mixed> $typedQueryParams */
            $typedQueryParams = $queryParams;
            return $typedQueryParams;
        }

        if ($method === 'POST') {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody)) {
                /** @var array<string, mixed> $typedParsedBody */
                $typedParsedBody = $parsedBody;
                return $typedParsedBody;
            }
            return [];
        }

        throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
    }

    /**
     * Create error response.
     */
    private function createErrorResponse(int $statusCode, string $reasonPhrase, string $message): ResponseInterface
    {
        $errorData = [
            'success' => false,
            'error'   => [
                'code'      => $statusCode,
                'message'   => $message,
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ];

        $response = $this->responseFactory->createResponse($statusCode, $reasonPhrase);
        $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
