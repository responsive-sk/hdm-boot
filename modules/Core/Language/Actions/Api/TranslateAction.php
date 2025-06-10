<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Actions\Api;

use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Translate Action - API endpoint for translations.
 * 
 * Based on samuelgfeller TranslateAction with enterprise enhancements.
 */
final class TranslateAction
{
    public function __construct(
        private readonly LocaleService $localeService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Translate strings via API.
     * 
     * Accepts:
     * - GET /api/translate?strings[]=Hello&strings[]=World
     * - POST /api/translate with JSON: {"strings": ["Hello", "World"]}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $strings = $this->extractStringsFromRequest($request);
            
            if (empty($strings)) {
                return $this->errorResponse($response, 'No strings provided for translation', 400);
            }

            $translations = $this->translateStrings($strings);
            
            $this->logger->info('Strings translated via API', [
                'count' => count($strings),
                'locale' => $this->localeService->getCurrentLocale(),
            ]);

            return $this->successResponse($response, $translations);
            
        } catch (\Exception $e) {
            $this->logger->error('Translation API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->errorResponse($response, 'Translation failed', 500);
        }
    }

    /**
     * Extract strings from request (GET or POST).
     * 
     * @return array<string>
     */
    private function extractStringsFromRequest(ServerRequestInterface $request): array
    {
        $method = $request->getMethod();
        
        if ($method === 'GET') {
            $queryParams = $request->getQueryParams();
            return $queryParams['strings'] ?? [];
        }
        
        if ($method === 'POST') {
            $body = (string) $request->getBody();
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON in request body');
            }
            
            return $data['strings'] ?? [];
        }
        
        return [];
    }

    /**
     * Translate array of strings.
     * 
     * @param array<string> $strings
     * @return array<string, string>
     */
    private function translateStrings(array $strings): array
    {
        $translations = [];
        
        foreach ($strings as $string) {
            if (!is_string($string)) {
                continue;
            }
            
            $translations[$string] = $this->localeService->translate($string);
        }
        
        return $translations;
    }

    /**
     * Create success JSON response.
     * 
     * @param array<string, string> $translations
     */
    private function successResponse(ResponseInterface $response, array $translations): ResponseInterface
    {
        $data = [
            'success' => true,
            'data' => [
                'translations' => $translations,
                'locale' => $this->localeService->getCurrentLocale(),
                'language_code' => $this->localeService->getCurrentLanguageCode(),
                'count' => count($translations),
            ],
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create error JSON response.
     */
    private function errorResponse(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $data = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status,
            ],
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
