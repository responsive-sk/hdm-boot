<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Exceptions;

/**
 * Exception thrown when a session key is not found.
 */
class SessionKeyNotFoundException extends SessionException
{
    public function __construct(string $key)
    {
        parent::__construct("Session key '{$key}' not found");
    }

    public static function forKey(string $key): self
    {
        return new self($key);
    }
}
