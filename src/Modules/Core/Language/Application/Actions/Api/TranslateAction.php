<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Application\Actions\Api;

use HdmBoot\Modules\Core\Language\Application\DTOs\TranslateRequest;
use HdmBoot\Modules\Core\Language\Application\Queries\GetTranslationQuery;
use HdmBoot\Modules\Core\Language\Domain\Services\TranslationService;
use HdmBoot\Modules\Core\Language\Domain\ValueObjects\Locale;
use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Exceptions\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Translate Action.
 *
 * HTTP adapter for translation functionality using DDD structure.
 */
final class TranslateAction
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle translation request.
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Parse request data
            $requestData = $this->parseRequestData($request);
            $translateRequest = TranslateRequest::fromArray($requestData);

            // Validate request
            $validationErrors = $translateRequest->validate();
            if (!empty($validationErrors)) {
                throw ValidationException::withErrors(['request' => $validationErrors]);
            }

            // Determine locale
            $locale = $translateRequest->locale
                ? Locale::fromString($translateRequest->locale)
                : $this->getCurrentLocale($request);

            // Create query
            $query = GetTranslationQuery::create(
                $translateRequest->key,
                $locale->toString(),
                $translateRequest->parameters,
                'en_US' // fallback locale
            );

            // Execute translation
            $translatedText = $this->translationService->translate(
                $query->key,
                $query->locale,
                $query->parameters,
                $query->fallbackLocale
            );

            // Log translation request
            $this->logger->info('Translation request processed', [
                'key'              => $translateRequest->key,
                'locale'           => $locale->toString(),
                'parameters_count' => count($translateRequest->parameters),
                'result_length'    => strlen($translatedText),
            ]);

            // Return response
            return $this->createSuccessResponse([
                'key'             => $translateRequest->key,
                'locale'          => $locale->toString(),
                'translated_text' => $translatedText,
                'parameters'      => $translateRequest->parameters,
            ]);
        } catch (ValidationException $e) {
            $this->logger->warning('Translation validation failed', [
                'errors'       => $e->getValidationErrors(),
                'request_data' => $requestData ?? [],
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Translation request failed', [
                'error'        => $e->getMessage(),
                'request_data' => $requestData ?? [],
            ]);
            throw $e;
        }
    }

    /**
     * Parse request data from different sources.
     *
     * @return array<string, mixed>
     */
    private function parseRequestData(ServerRequestInterface $request): array
    {
        $method = $request->getMethod();

        if ($method === 'POST') {
            $parsedBody = $request->getParsedBody();
            /** @var array<string, mixed> $postData */
            $postData = is_array($parsedBody) ? $parsedBody : [];
            return $postData;
        }

        if ($method === 'GET') {
            /** @var array<string, mixed> $queryParams */
            $queryParams = $request->getQueryParams();
            return $queryParams;
        }

        return [];
    }

    /**
     * Get current locale from request.
     */
    private function getCurrentLocale(ServerRequestInterface $request): Locale
    {
        // Try to get locale from session, headers, or default
        $sessionLocale = $_SESSION['locale'] ?? null;
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        $localeCode = $sessionLocale ?: ($acceptLanguage ?: 'en_US');

        // Ensure locale code is string
        $localeCodeString = is_string($localeCode) ? $localeCode : 'en_US';

        // Parse Accept-Language header if needed
        if (str_contains($localeCodeString, ',')) {
            $parts = explode(',', $localeCodeString);
            $localeCodeString = $parts[0];
        }

        // Convert to our format if needed
        if (str_contains($localeCodeString, '-')) {
            $localeCodeString = str_replace('-', '_', $localeCodeString);
        }

        // Validate and fallback to default
        try {
            return Locale::fromString($localeCodeString);
        } catch (\Exception $e) {
            return Locale::default();
        }
    }

    /**
     * Create success response.
     *
     * @param array<string, mixed> $data
     */
    private function createSuccessResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'application/json');

        $responseData = [
            'success'   => true,
            'data'      => $data,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
        ];

        $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));

        return $response;
    }
}
