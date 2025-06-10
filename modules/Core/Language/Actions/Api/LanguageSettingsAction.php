<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Actions\Api;

use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Language Settings Action - Manage language preferences.
 */
final class LanguageSettingsAction
{
    public function __construct(
        private readonly LocaleService $localeService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle language settings requests.
     * 
     * GET: Get current language settings
     * POST: Set new language
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = $request->getMethod();
        
        try {
            return match ($method) {
                'GET' => $this->getLanguageSettings($response),
                'POST' => $this->setLanguage($request, $response),
                default => $this->errorResponse($response, 'Method not allowed', 405),
            };
            
        } catch (\Exception $e) {
            $this->logger->error('Language settings error', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            
            return $this->errorResponse($response, 'Language settings operation failed', 500);
        }
    }

    /**
     * Get current language settings.
     */
    private function getLanguageSettings(ResponseInterface $response): ResponseInterface
    {
        $availableLocales = [];
        
        foreach ($this->localeService->getAvailableLocales() as $locale) {
            $availableLocales[] = [
                'code' => $locale,
                'name' => $this->localeService->getLocaleDisplayName($locale),
                'native_name' => $this->localeService->getLocaleNativeName($locale),
                'flag' => $this->localeService->getLocaleFlag($locale),
                'language_code' => explode('_', $locale)[0],
            ];
        }

        $data = [
            'success' => true,
            'data' => [
                'current_locale' => $this->localeService->getCurrentLocale(),
                'current_language_code' => $this->localeService->getCurrentLanguageCode(),
                'available_locales' => $availableLocales,
                'language_path' => $this->localeService->getLanguageCodeForPath(),
            ],
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Set new language.
     */
    private function setLanguage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->errorResponse($response, 'Invalid JSON in request body', 400);
        }

        $locale = $data['locale'] ?? null;
        
        if (!$locale) {
            return $this->errorResponse($response, 'Locale is required', 400);
        }

        if (!$this->localeService->isLocaleSupported($locale)) {
            return $this->errorResponse($response, 'Unsupported locale', 400);
        }

        $result = $this->localeService->setLanguage($locale);
        
        if ($result === false) {
            return $this->errorResponse($response, 'Failed to set language', 500);
        }

        $this->logger->info('Language changed via API', [
            'new_locale' => $locale,
            'previous_locale' => $this->localeService->getCurrentLocale(),
        ]);

        $responseData = [
            'success' => true,
            'data' => [
                'locale' => $this->localeService->getCurrentLocale(),
                'language_code' => $this->localeService->getCurrentLanguageCode(),
                'message' => 'Language set successfully',
            ],
        ];

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
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
