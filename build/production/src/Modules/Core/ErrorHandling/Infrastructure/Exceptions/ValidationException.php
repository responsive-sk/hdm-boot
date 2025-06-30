<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Exceptions;

use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * Validation Exception.
 *
 * Thrown when input validation fails.
 */
final class ValidationException extends ProblemDetailsException
{
    /**
     * Create validation exception with validation errors.
     *
     * @param array<string, string|array<string>> $validationErrors
     */
    public static function withErrors(
        array $validationErrors,
        string $detail = 'The request contains invalid data',
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::validationError(
            detail: $detail,
            validationErrors: $validationErrors,
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create validation exception for a single field.
     */
    public static function forField(
        string $field,
        string $message,
        ?string $instance = null
    ): self {
        return self::withErrors(
            validationErrors: [$field => $message],
            detail: "Validation failed for field: {$field}",
            instance: $instance
        );
    }

    /**
     * Create validation exception for required field.
     */
    public static function requiredField(
        string $field,
        ?string $instance = null
    ): self {
        return self::forField(
            field: $field,
            message: "The {$field} field is required",
            instance: $instance
        );
    }

    /**
     * Create validation exception for invalid format.
     */
    public static function invalidFormat(
        string $field,
        string $expectedFormat,
        ?string $instance = null
    ): self {
        return self::forField(
            field: $field,
            message: "The {$field} field must be a valid {$expectedFormat}",
            instance: $instance
        );
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string|array<string>>
     */
    public function getValidationErrors(): array
    {
        $validationErrors = $this->problemDetails->extensions['validation_errors'] ?? [];

        if (!is_array($validationErrors)) {
            return [];
        }

        // Ensure all values are strings or arrays of strings
        $result = [];
        foreach ($validationErrors as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($value)) {
                $result[$key] = $value;
            } elseif (is_array($value)) {
                // Ensure all array elements are strings
                $stringArray = [];
                foreach ($value as $item) {
                    if (is_string($item)) {
                        $stringArray[] = $item;
                    }
                }
                $result[$key] = $stringArray;
            }
        }

        return $result;
    }
}
