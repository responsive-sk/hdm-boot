<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Exceptions;

/**
 * Base session exception.
 */
class SessionException extends \RuntimeException
{
    public static function notStarted(): self
    {
        return new self('Session is not started');
    }

    public static function alreadyStarted(): self
    {
        return new self('Session is already started');
    }

    public static function cannotStart(string $reason = ''): self
    {
        $message = 'Cannot start session';
        if ($reason) {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }

    public static function cannotDestroy(string $reason = ''): self
    {
        $message = 'Cannot destroy session';
        if ($reason) {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }
}
