<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Helpers;

use MvaBootstrap\Modules\Core\User\Exceptions\UserAlreadyExistsException;
use MvaBootstrap\Modules\Core\User\Exceptions\UserNotFoundException;
use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions\AuthenticationException;
use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions\AuthorizationException;
use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions\SecurityException;
use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions\ValidationException;

/**
 * Error Helper.
 *
 * Provides convenient methods for throwing standardized exceptions
 * with proper Problem Details formatting.
 */
final class ErrorHelper
{
    /**
     * Throw validation error for required field.
     */
    public static function throwRequiredField(string $field, ?string $instance = null): never
    {
        throw ValidationException::requiredField($field, $instance);
    }

    /**
     * Throw validation error for invalid format.
     */
    public static function throwInvalidFormat(
        string $field,
        string $expectedFormat,
        ?string $instance = null
    ): never {
        throw ValidationException::invalidFormat($field, $expectedFormat, $instance);
    }

    /**
     * Throw validation error with multiple errors.
     *
     * @param array<string, string|array<string>> $validationErrors
     */
    public static function throwValidationErrors(
        array $validationErrors,
        string $detail = 'The request contains invalid data',
        ?string $instance = null
    ): never {
        throw ValidationException::withErrors($validationErrors, $detail, $instance);
    }

    /**
     * Throw authentication error for invalid credentials.
     */
    public static function throwInvalidCredentials(?string $instance = null): never
    {
        throw AuthenticationException::invalidCredentials($instance);
    }

    /**
     * Throw authentication error for missing credentials.
     */
    public static function throwMissingCredentials(?string $instance = null): never
    {
        throw AuthenticationException::missingCredentials($instance);
    }

    /**
     * Throw authentication error for expired token.
     */
    public static function throwExpiredToken(?string $instance = null): never
    {
        throw AuthenticationException::expiredToken($instance);
    }

    /**
     * Throw authentication error for invalid token.
     */
    public static function throwInvalidToken(?string $instance = null): never
    {
        throw AuthenticationException::invalidToken($instance);
    }

    /**
     * Throw authorization error for insufficient permissions.
     */
    public static function throwInsufficientPermissions(
        string $requiredPermission,
        ?string $instance = null
    ): never {
        throw AuthorizationException::insufficientPermissions($requiredPermission, $instance);
    }

    /**
     * Throw authorization error for access denied.
     */
    public static function throwAccessDenied(?string $instance = null): never
    {
        throw AuthorizationException::accessDenied($instance);
    }

    /**
     * Throw authorization error for resource access.
     */
    public static function throwResourceAccessDenied(
        string $resource,
        string $action = 'access',
        ?string $instance = null
    ): never {
        throw AuthorizationException::resourceAccessDenied($resource, $action, $instance);
    }

    /**
     * Throw user not found error by ID.
     */
    public static function throwUserNotFoundById(string $userId, ?string $instance = null): never
    {
        throw UserNotFoundException::byId($userId, $instance);
    }

    /**
     * Throw user not found error by email.
     */
    public static function throwUserNotFoundByEmail(string $email, ?string $instance = null): never
    {
        throw UserNotFoundException::byEmail($email, $instance);
    }

    /**
     * Throw user already exists error.
     */
    public static function throwUserAlreadyExists(string $email, ?string $instance = null): never
    {
        throw UserAlreadyExistsException::withEmail($email, $instance);
    }

    /**
     * Throw rate limit exceeded error.
     */
    public static function throwRateLimitExceeded(
        string $detail = 'Rate limit exceeded',
        ?int $retryAfter = null,
        ?string $instance = null
    ): never {
        throw SecurityException::rateLimitExceeded($detail, $retryAfter, $instance);
    }

    /**
     * Throw CSRF token validation error.
     */
    public static function throwInvalidCsrfToken(?string $instance = null): never
    {
        throw SecurityException::invalidCsrfToken($instance);
    }

    /**
     * Validate required string field.
     */
    public static function validateRequired(
        string $value,
        string $fieldName,
        ?string $instance = null
    ): void {
        if (empty(trim($value))) {
            self::throwRequiredField($fieldName, $instance);
        }
    }

    /**
     * Validate email format.
     */
    public static function validateEmail(
        string $email,
        string $fieldName = 'email',
        ?string $instance = null
    ): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::throwInvalidFormat($fieldName, 'email address', $instance);
        }
    }

    /**
     * Validate minimum length.
     */
    public static function validateMinLength(
        string $value,
        int $minLength,
        string $fieldName,
        ?string $instance = null
    ): void {
        if (strlen($value) < $minLength) {
            self::throwInvalidFormat(
                $fieldName,
                "string with at least {$minLength} characters",
                $instance
            );
        }
    }

    /**
     * Validate that value is in allowed list.
     *
     * @param array<string> $allowedValues
     */
    public static function validateInList(
        string $value,
        array $allowedValues,
        string $fieldName,
        ?string $instance = null
    ): void {
        if (!in_array($value, $allowedValues, true)) {
            $allowedList = implode(', ', $allowedValues);
            self::throwInvalidFormat(
                $fieldName,
                "one of: {$allowedList}",
                $instance
            );
        }
    }

    /**
     * Assert that condition is true, throw validation error if false.
     */
    public static function assertTrue(
        bool $condition,
        string $fieldName,
        string $message,
        ?string $instance = null
    ): void {
        if (!$condition) {
            throw ValidationException::forField($fieldName, $message, $instance);
        }
    }
}
