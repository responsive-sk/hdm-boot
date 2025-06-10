<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Feature;

use MvaBootstrap\Tests\TestCase;

/**
 * Simple API tests to verify basic functionality.
 */
class SimpleApiTest extends TestCase
{
    /**
     * @covers \MvaBootstrap\Modules\Core\Security\Actions\LoginAction
     */
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

    /**
     * @covers \MvaBootstrap\Modules\Core\Security\Actions\MeAction
     */
    public function testMeEndpoint(): void
    {
        $token = $this->loginAndGetToken();
        $request = $this->createAuthenticatedRequest('GET', '/api/auth/me', $token);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertSame('admin@example.com', $data['data']['user']['email']);
    }

    /**
     * @covers \MvaBootstrap\Modules\Core\User\Actions\ListUsersAction
     */
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

    /**
     * @covers \MvaBootstrap\Modules\Core\User\Actions\ListUsersAction
     */
    public function testUnauthorizedAccess(): void
    {
        $request = $this->createRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401);
    }

    /**
     * @covers \MvaBootstrap\Modules\Core\Security\Actions\LoginAction
     */
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
