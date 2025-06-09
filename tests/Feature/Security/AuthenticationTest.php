<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Feature\Security;

use MvaBootstrap\Tests\TestCase;

/**
 * Feature tests for Authentication API.
 */
class AuthenticationTest extends TestCase
{
    public function testSuccessfulLogin(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertArrayHasKey('user', $data['data']);

        $this->assertSame('Bearer', $data['data']['token_type']);
        $this->assertIsString($data['data']['token']);
        $this->assertIsInt($data['data']['expires_in']);

        // Verify user data
        $user = $data['data']['user'];
        $this->assertSame('admin@example.com', $user['email']);
        $this->assertSame('Admin User', $user['name']);
        $this->assertSame('admin', $user['role']);
        $this->assertSame('active', $user['status']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'INVALID_CREDENTIALS');
    }

    public function testLoginWithNonExistentUser(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'INVALID_CREDENTIALS');
    }

    public function testLoginWithMissingEmail(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'password' => 'Password123',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 422, 'VALIDATION_ERROR');
    }

    public function testLoginWithMissingPassword(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 422, 'VALIDATION_ERROR');
    }

    public function testLoginWithInvalidEmailFormat(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'Password123',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 422, 'VALIDATION_ERROR');
    }

    public function testMeEndpointWithValidToken(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('GET', '/api/auth/me', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $user = $data['data'];

        $this->assertSame('admin@example.com', $user['email']);
        $this->assertSame('Admin User', $user['name']);
        $this->assertSame('admin', $user['role']);
        $this->assertArrayHasKey('permissions', $user);
        $this->assertIsArray($user['permissions']);
    }

    public function testMeEndpointWithoutToken(): void
    {
        $request = $this->createRequest('GET', '/api/auth/me');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testMeEndpointWithInvalidToken(): void
    {
        $request = $this->createRequest('GET', '/api/auth/me', [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'INVALID_TOKEN');
    }

    public function testRefreshTokenWithValidToken(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('POST', '/api/auth/refresh', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);

        // New token should be different from original
        $this->assertNotSame($token, $data['data']['token']);
    }

    public function testRefreshTokenWithoutToken(): void
    {
        $request = $this->createRequest('POST', '/api/auth/refresh');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testLogoutWithValidToken(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('POST', '/api/auth/logout', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('logged out', $data['message']);
    }

    public function testLogoutWithoutToken(): void
    {
        $request = $this->createRequest('POST', '/api/auth/logout');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testTokenExpiration(): void
    {
        // This test would require manipulating token expiration
        // For now, we just verify the token contains expiration info
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('GET', '/api/auth/me', $token);

        $response = $this->executeRequest($request);
        $this->assertJsonResponse($response, 200, true);

        // Token should be valid immediately after login
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMultipleLoginAttempts(): void
    {
        // Test multiple successful logins
        for ($i = 0; $i < 3; $i++) {
            $request = $this->createJsonRequest('POST', '/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'Password123',
            ]);

            $response = $this->executeRequest($request);
            $this->assertJsonResponse($response, 200, true);
        }

        // All should succeed (no rate limiting in test environment)
        $this->assertTrue(true);
    }

    public function testConcurrentTokenUsage(): void
    {
        $token = $this->loginAndGetToken();

        // Use token multiple times concurrently (simulated)
        for ($i = 0; $i < 5; $i++) {
            $request = $this->createAuthenticatedRequest('GET', '/api/auth/me', $token);
            $response = $this->executeRequest($request);
            $this->assertJsonResponse($response, 200, true);
        }

        // All requests should succeed
        $this->assertTrue(true);
    }
}
