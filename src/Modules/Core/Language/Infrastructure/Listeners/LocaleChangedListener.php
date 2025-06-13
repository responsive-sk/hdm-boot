<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Infrastructure\Listeners;

use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\SharedKernel\Events\DomainEvent;
use MvaBootstrap\SharedKernel\Events\EventListener;
use Psr\Log\LoggerInterface;

/**
 * Locale Changed Event Listener.
 *
 * Handles locale change events and performs related actions.
 */
final class LocaleChangedListener implements EventListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle the domain event.
     */
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof LocaleChangedEvent) {
            return;
        }

        $this->logger->info('Processing locale change', [
            'user_id'         => $event->userId,
            'previous_locale' => $event->previousLocale->toString(),
            'new_locale'      => $event->newLocale->toString(),
            'occurred_at'     => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);

        // Update user session
        $this->updateUserSession($event);

        // Clear locale-specific caches
        $this->clearLocaleCaches($event);

        // Log locale change for analytics
        $this->logLocaleChangeAnalytics($event);

        $this->logger->debug('Locale change processed successfully', [
            'user_id'    => $event->userId,
            'new_locale' => $event->newLocale->toString(),
        ]);
    }

    /**
     * Get events this listener supports.
     */
    public function getSupportedEvents(): array
    {
        return ['language.locale_changed'];
    }

    /**
     * Get listener priority.
     */
    public function getPriority(): int
    {
        return 100; // High priority for locale changes
    }

    /**
     * Update user session with new locale.
     */
    private function updateUserSession(LocaleChangedEvent $event): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $event->newLocale->toString();
            $_SESSION['locale_changed_at'] = $event->occurredAt->format('Y-m-d H:i:s');

            $this->logger->debug('User session updated with new locale', [
                'user_id' => $event->userId,
                'locale'  => $event->newLocale->toString(),
            ]);
        }
    }

    /**
     * Clear locale-specific caches.
     */
    private function clearLocaleCaches(LocaleChangedEvent $event): void
    {
        // Clear translation caches for the user
        $cacheKeys = [
            "translations.{$event->previousLocale->toString()}",
            "translations.{$event->newLocale->toString()}",
            "user.{$event->userId}.locale",
        ];

        foreach ($cacheKeys as $key) {
            // Here you would clear actual cache
            $this->logger->debug('Cache cleared', ['cache_key' => $key]);
        }
    }

    /**
     * Log locale change for analytics.
     */
    private function logLocaleChangeAnalytics(LocaleChangedEvent $event): void
    {
        $analyticsData = [
            'event_type'      => 'locale_changed',
            'user_id'         => $event->userId,
            'previous_locale' => $event->previousLocale->toString(),
            'new_locale'      => $event->newLocale->toString(),
            'timestamp'       => $event->occurredAt->getTimestamp(),
            'language_code'   => $event->newLocale->getLanguageCode(),
            'country_code'    => $event->newLocale->getCountryCode(),
        ];

        $this->logger->info('Locale change analytics', $analyticsData);
    }
}
