<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Exception;

use Exception;

/**
 * Validation Exception.
 *
 * Thrown when validation fails.
 */
class ValidationException extends Exception
{
    /**
     * @param array<string, array<string>> $errors Validation errors
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed',
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message.
     */
    public function getFirstError(): string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return $this->getMessage();
    }

    /**
     * Get all error messages as flat array.
     *
     * @return array<string>
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }

        return $messages;
    }
}
