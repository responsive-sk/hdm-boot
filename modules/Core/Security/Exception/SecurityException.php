<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Exception;

use MvaBootstrap\Modules\Core\Security\Enum\SecurityType;
use RuntimeException;

/**
 * Security Exception.
 *
 * Thrown when security checks fail (throttling, captcha, etc.).
 * Adapted from the original Security module.
 */
class SecurityException extends RuntimeException
{
    public function __construct(
        private readonly int|string $remainingDelay,
        private readonly SecurityType $securityType,
        string $message = 'Security check failed.',
    ) {
        parent::__construct($message);
    }

    /**
     * Get remaining delay (int for seconds or 'captcha' string).
     */
    public function getRemainingDelay(): int|string
    {
        return $this->remainingDelay;
    }

    /**
     * Get security type that triggered the exception.
     */
    public function getSecurityType(): SecurityType
    {
        return $this->securityType;
    }

    /**
     * Get user-friendly public message.
     */
    public function getPublicMessage(): string
    {
        $userThrottleMessage = is_numeric($this->remainingDelay) ?
            sprintf('wait %s seconds', '<span class="throttle-time-span">' . $this->remainingDelay . '</span>')
            : 'fill out the captcha';

        return match ($this->getSecurityType()) {
            SecurityType::USER_LOGIN, SecurityType::USER_EMAIL => sprintf(
                'It looks like you are doing this too much. Please %s and try again.',
                $userThrottleMessage
            ),
            SecurityType::GLOBAL_LOGIN, SecurityType::GLOBAL_EMAIL => 'The site is under a too high request load. Therefore, a general throttling is in place. Please fill out the captcha and try again.',
            SecurityType::GLOBAL_REQUESTS => 'Too many requests. Please wait or fill out the captcha and try again.',
            default                       => 'Please wait or fill out the captcha and repeat the action.',
        };
    }

    /**
     * Convert to array for API responses.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code'             => 'SECURITY_THROTTLE',
            'message'          => $this->getPublicMessage(),
            'security_type'    => $this->securityType->value,
            'remaining_delay'  => $this->remainingDelay,
            'requires_captcha' => $this->remainingDelay === 'captcha',
        ];
    }
}
