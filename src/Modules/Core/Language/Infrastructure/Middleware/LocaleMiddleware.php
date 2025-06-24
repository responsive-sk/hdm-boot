<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Infrastructure\Middleware;

use HdmBoot\Modules\Core\Language\Services\LocaleService;
use HdmBoot\Modules\Core\User\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Session\SessionInterface;

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
        // TODO: Re-enable when User entity has language preference field
        // private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process request and set appropriate locale.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $detectedLocale = null;

        try {
            $detectedLocale = $this->detectLocale($request);
            $result = $this->localeService->setLanguage($detectedLocale);

            if ($result !== false) {
                // Store in session for future requests
                $this->session->set('app_language', $detectedLocale);

                $this->logger->debug('Locale set via middleware', [
                    'locale'           => $detectedLocale,
                    'detection_method' => $this->getDetectionMethod($request, $detectedLocale),
                ]);
            } else {
                $this->logger->warning('Failed to set locale via middleware', [
                    'locale' => $detectedLocale,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Locale middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $detectedLocale = null; // Reset on error
        }

        // Continue to next middleware/handler
        $response = $handler->handle($request);

        // Set language cookie in response if locale was detected
        if ($detectedLocale !== null) {
            $response = $this->setLanguageCookie($response, $detectedLocale);
        }

        return $response;
    }

    /**
     * Detect appropriate locale based on priority order.
     */
    private function detectLocale(ServerRequestInterface $request): string
    {
        $config = $this->localeService->getConfig();
        $detection = is_array($config['detection'] ?? null) ? $config['detection'] : [];

        // 1. User preference from database (if authenticated)
        // TODO: Implement when User entity has language preference field
        // $useUserPreference = $detection['use_user_preference'] ?? true;
        // if (is_bool($useUserPreference) ? $useUserPreference : true) {
        //     $userLocale = $this->getUserLocale();
        //     if ($userLocale && $this->localeService->isLocaleSupported($userLocale)) {
        //         return $userLocale;
        //     }
        // }

        // 2. Session language preference
        $useSession = $detection['use_session'] ?? true;
        if (is_bool($useSession) ? $useSession : true) {
            $sessionLocale = $this->session->get('app_language');
            $sessionLocaleString = is_string($sessionLocale) ? $sessionLocale : null;
            if ($sessionLocaleString && $this->localeService->isLocaleSupported($sessionLocaleString)) {
                return $sessionLocaleString;
            }
        }

        // 3. Cookie language preference
        $useCookie = $detection['use_cookie'] ?? true;
        if (is_bool($useCookie) ? $useCookie : true) {
            $cookieLocale = $this->getCookieLocale($request);
            if ($cookieLocale && $this->localeService->isLocaleSupported($cookieLocale)) {
                return $cookieLocale;
            }
        }

        // 4. Browser Accept-Language header
        $useBrowserLanguage = $detection['use_browser_language'] ?? true;
        if (is_bool($useBrowserLanguage) ? $useBrowserLanguage : true) {
            $browserLocale = $this->getBrowserLocale($request);
            if ($browserLocale && $this->localeService->isLocaleSupported($browserLocale)) {
                return $browserLocale;
            }
        }

        // 5. Default locale from config
        $defaultLocale = $config['default_locale'] ?? 'en_US';

        return is_string($defaultLocale) ? $defaultLocale : 'en_US';
    }

    // TODO: Implement getUserLocale() when User entity has language preference field
    // private function getUserLocale(): ?string
    // {
    //     try {
    //         $userId = $this->session->get('user_id');
    //         $userIdString = is_string($userId) ? $userId : null;
    //
    //         if (!$userIdString) {
    //             return null;
    //         }
    //
    //         $user = $this->userService->getUserById($userIdString);
    //
    //         if (!$user || !isset($user['language'])) {
    //             return null;
    //         }
    //
    //         return is_string($user['language']) ? $user['language'] : null;
    //     } catch (\Exception $e) {
    //         $this->logger->warning('Failed to get user locale', [
    //             'error' => $e->getMessage(),
    //         ]);
    //
    //         return null;
    //     }
    // }

    /**
     * Get locale from cookie.
     */
    private function getCookieLocale(ServerRequestInterface $request): ?string
    {
        $config = $this->localeService->getConfig();
        $detection = is_array($config['detection'] ?? null) ? $config['detection'] : [];
        $cookieNameValue = $detection['cookie_name'] ?? 'app_language';
        $cookieName = is_string($cookieNameValue) ? $cookieNameValue : 'app_language';

        $cookies = $request->getCookieParams();
        $cookieValue = $cookies[$cookieName] ?? null;

        return is_string($cookieValue) ? $cookieValue : null;
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
        $detection = is_array($config['detection'] ?? null) ? $config['detection'] : [];

        $useCookie = $detection['use_cookie'] ?? true;
        if (!(is_bool($useCookie) ? $useCookie : true)) {
            return $response;
        }

        $cookieNameValue = $detection['cookie_name'] ?? 'app_language';
        $cookieName = is_string($cookieNameValue) ? $cookieNameValue : 'app_language';

        $cookieLifetimeValue = $detection['cookie_lifetime'] ?? 2592000; // 30 days
        $cookieLifetime = is_int($cookieLifetimeValue) ? $cookieLifetimeValue : 2592000;

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

        // TODO: Implement when User entity has language preference field
        // if ($userId && $this->getUserLocale() === $locale) {
        //     return 'user_preference';
        // }

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
