<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Feature;

use MvaBootstrap\Tests\TestCase;

/**
 * Simple API tests to verify basic functionality.
 */
class SimpleApiTest extends TestCase
{
    public function testLoginEndpoint(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertIsString($data['data']['token']);
    }

    public function testMeEndpoint(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('GET', '/api/auth/me', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertSame('admin@example.com', $data['data']['email']);
    }

    public function testUsersEndpoint(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('GET', '/api/users', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThanOrEqual(1, count($data['data']));
    }

    public function testUnauthorizedAccess(): void
    {
        $request = $this->createRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401);
    }

    public function testInvalidLogin(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401);
    }
}
