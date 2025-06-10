<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Middleware;

use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Locale Middleware - Automatic Language Detection and Setting.
 *
 * Based on samuelgfeller LocaleMiddleware with enterprise enhancements.
 *
 * Priority order:
 * 1. User preference from database (if authenticated)
 * 2. Session language preference
 * 3. Cookie language preference
 * 4. Browser Accept-Language header
 * 5. Default locale from config
 */
final class LocaleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LocaleService $localeService,
        private readonly SessionInterface $session,
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process request and set appropriate locale.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $locale = $this->detectLocale($request);

            if ($locale) {
                $result = $this->localeService->setLanguage($locale);

                if ($result !== false) {
                    // Store in session for future requests
                    $this->session->set('app_language', $locale);

                    $this->logger->debug('Locale set via middleware', [
                        'locale' => $locale,
                        'detection_method' => $this->getDetectionMethod($request, $locale),
                    ]);
                } else {
                    $this->logger->warning('Failed to set locale via middleware', [
                        'locale' => $locale,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Locale middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Continue to next middleware/handler
        $response = $handler->handle($request);

        // Set language cookie in response if locale was detected
        if (isset($locale) && $locale) {
            $response = $this->setLanguageCookie($response, $locale);
        }

        return $response;
    }

    /**
     * Detect appropriate locale based on priority order.
     */
    private function detectLocale(ServerRequestInterface $request): ?string
    {
        $config = $this->localeService->getConfig();
        $detection = $config['detection'] ?? [];

        // 1. User preference from database (if authenticated)
        if ($detection['use_user_preference'] ?? true) {
            $userLocale = $this->getUserLocale();
            if ($userLocale && $this->localeService->isLocaleSupported($userLocale)) {
                return $userLocale;
            }
        }

        // 2. Session language preference
        if ($detection['use_session'] ?? true) {
            $sessionLocale = $this->session->get('app_language');
            if ($sessionLocale && $this->localeService->isLocaleSupported($sessionLocale)) {
                return $sessionLocale;
            }
        }

        // 3. Cookie language preference
        if ($detection['use_cookie'] ?? true) {
            $cookieLocale = $this->getCookieLocale($request);
            if ($cookieLocale && $this->localeService->isLocaleSupported($cookieLocale)) {
                return $cookieLocale;
            }
        }

        // 4. Browser Accept-Language header
        if ($detection['use_browser_language'] ?? true) {
            $browserLocale = $this->getBrowserLocale($request);
            if ($browserLocale && $this->localeService->isLocaleSupported($browserLocale)) {
                return $browserLocale;
            }
        }

        // 5. Default locale from config
        return $config['default_locale'] ?? 'en_US';
    }

    /**
     * Get user's preferred locale from database.
     */
    private function getUserLocale(): ?string
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                return null;
            }

            $user = $this->userService->getUserById($userId);

            if (!$user) {
                return null;
            }

            // TODO: Add language field to User entity
            // For now, return null - this would be implemented when User has language preference
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get user locale', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get locale from cookie.
     */
    private function getCookieLocale(ServerRequestInterface $request): ?string
    {
        $config = $this->localeService->getConfig();
        $cookieName = $config['detection']['cookie_name'] ?? 'app_language';

        $cookies = $request->getCookieParams();

        return $cookies[$cookieName] ?? null;
    }

    /**
     * Get locale from browser Accept-Language header.
     */
    private function getBrowserLocale(ServerRequestInterface $request): ?string
    {
        $acceptLanguage = $request->getHeaderLine('Accept-Language');

        if (empty($acceptLanguage)) {
            return null;
        }

        // Parse Accept-Language header
        // Example: "en-GB,en;q=0.9,de;q=0.8,de-DE;q=0.7,en-US;q=0.6"
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            // Remove quality factor (q=0.9)
            $locale = trim(explode(';', $language)[0]);

            // Convert hyphen to underscore (en-GB -> en_GB)
            $locale = str_replace('-', '_', $locale);

            // Try exact match first
            if ($this->localeService->isLocaleSupported($locale)) {
                return $locale;
            }

            // Try language code only (en_GB -> en_US if available)
            $languageCode = explode('_', $locale)[0];
            foreach ($this->localeService->getAvailableLocales() as $availableLocale) {
                if (str_starts_with($availableLocale, $languageCode . '_')) {
                    return $availableLocale;
                }
            }
        }

        return null;
    }

    /**
     * Set language cookie for persistence.
     */
    private function setLanguageCookie(ResponseInterface $response, string $locale): ResponseInterface
    {
        $config = $this->localeService->getConfig();
        $detection = $config['detection'] ?? [];

        if (!($detection['use_cookie'] ?? true)) {
            return $response;
        }

        $cookieName = $detection['cookie_name'] ?? 'app_language';
        $cookieLifetime = $detection['cookie_lifetime'] ?? 2592000; // 30 days

        $cookieValue = sprintf(
            '%s=%s; Max-Age=%d; Path=/; HttpOnly; SameSite=Lax',
            $cookieName,
            $locale,
            $cookieLifetime
        );

        return $response->withAddedHeader('Set-Cookie', $cookieValue);
    }

    /**
     * Get detection method for logging.
     */
    private function getDetectionMethod(ServerRequestInterface $request, string $locale): string
    {
        $userId = $this->session->get('user_id');
        $sessionLocale = $this->session->get('app_language');
        $cookieLocale = $this->getCookieLocale($request);
        $browserLocale = $this->getBrowserLocale($request);

        if ($userId && $this->getUserLocale() === $locale) {
            return 'user_preference';
        }

        if ($sessionLocale === $locale) {
            return 'session';
        }

        if ($cookieLocale === $locale) {
            return 'cookie';
        }

        if ($browserLocale === $locale) {
            return 'browser';
        }

        return 'default';
    }
}
