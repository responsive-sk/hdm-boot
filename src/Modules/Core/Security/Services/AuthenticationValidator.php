<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Services;

use Cake\Validation\Validator;
use HdmBoot\Modules\Core\Security\Exceptions\ValidationException;

/**
 * Authentication Validator.
 *
 * Validates authentication-related inputs using CakePHP Validator.
 * Inspired by samuelgfeller/slim-example-project.
 */
final class AuthenticationValidator
{
    /**
     * Validate user login inputs.
     *
     * @param array<string, mixed> $userLoginValues
     *
     * @throws ValidationException
     */
    public function validateUserLogin(array $userLoginValues): void
    {
        $validator = new Validator();

        // Email validation
        $validator
            ->requirePresence('email', true, 'Email is required')
            ->email('email', false, 'Invalid email format')
            ->notEmptyString('email', 'Email cannot be empty');

        // Password validation
        $validator
            ->requirePresence('password', true, 'Password is required')
            ->notEmptyString('password', 'Password cannot be empty');

        // CSRF token validation (optional for API, required for web)
        $validator
            ->requirePresence('csrf_token', false); // Optional key

        // Validate and throw exception if there are errors
        $errors = $validator->validate($userLoginValues);
        if (!empty($errors)) {
            // CakePHP validator returns array<string, array<string>> format
            /** @var array<string, array<string>> $validationErrors */
            $validationErrors = $errors;
            throw new ValidationException($validationErrors);
        }
    }

    /**
     * Validate password requirements.
     *
     * @param array<string, mixed> $passwordValues
     *
     * @throws ValidationException
     */
    public function validatePassword(array $passwordValues): void
    {
        $validator = new Validator();

        $validator
            ->requirePresence('password', true, 'Password is required')
            ->notEmptyString('password', 'Password cannot be empty')
            ->minLength('password', 8, 'Password must be at least 8 characters long')
            ->maxLength('password', 1000, 'Password is too long')
            ->add('password', 'hasLowercase', [
                'rule' => function ($value) {
                    return is_string($value) && preg_match('/[a-z]/', $value);
                },
                'message' => 'Password must contain at least one lowercase letter',
            ])
            ->add('password', 'hasUppercase', [
                'rule' => function ($value) {
                    return is_string($value) && preg_match('/[A-Z]/', $value);
                },
                'message' => 'Password must contain at least one uppercase letter',
            ])
            ->add('password', 'hasNumber', [
                'rule' => function ($value) {
                    return is_string($value) && preg_match('/\d/', $value);
                },
                'message' => 'Password must contain at least one number',
            ]);

        // Validate and throw exception if there are errors
        $errors = $validator->validate($passwordValues);
        if (!empty($errors)) {
            // CakePHP validator returns array<string, array<string>> format
            /** @var array<string, array<string>> $validationErrors */
            $validationErrors = $errors;
            throw new ValidationException($validationErrors);
        }
    }

    /**
     * Validate password confirmation.
     *
     * @param array<string, mixed> $passwordValues
     *
     * @throws ValidationException
     */
    public function validatePasswordWithConfirmation(array $passwordValues): void
    {
        // First validate password requirements
        $this->validatePassword($passwordValues);

        $validator = new Validator();

        $validator
            ->requirePresence('password2', true, 'Password confirmation is required')
            ->notEmptyString('password2', 'Password confirmation cannot be empty')
            ->add('password2', 'passwordsMatch', [
                'rule' => function ($value, $context) {
                    if (!is_array($context) || !isset($context['data']) || !is_array($context['data'])) {
                        return false;
                    }
                    return isset($context['data']['password']) && $value === $context['data']['password'];
                },
                'message' => 'Passwords do not match',
            ]);

        // Validate and throw exception if there are errors
        $errors = $validator->validate($passwordValues);
        if (!empty($errors)) {
            // CakePHP validator returns array<string, array<string>> format
            /** @var array<string, array<string>> $validationErrors */
            $validationErrors = $errors;
            throw new ValidationException($validationErrors);
        }
    }

    /**
     * Validate email for password recovery.
     *
     * @param array<string, mixed> $userValues
     *
     * @throws ValidationException
     */
    public function validatePasswordResetEmail(array $userValues): void
    {
        $validator = new Validator();

        // Intentionally not validating user existence as it would be a security flaw
        $validator
            ->requirePresence('email', true, 'Email is required')
            ->email('email', false, 'Invalid email format')
            ->notEmptyString('email', 'Email cannot be empty');

        // Validate and throw exception if there are errors
        $errors = $validator->validate($userValues);
        if (!empty($errors)) {
            // CakePHP validator returns array<string, array<string>> format
            /** @var array<string, array<string>> $validationErrors */
            $validationErrors = $errors;
            throw new ValidationException($validationErrors);
        }
    }
}
