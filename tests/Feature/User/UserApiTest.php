<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Feature\User;

use MvaBootstrap\Tests\TestCase;

/**
 * Feature tests for User API.
 */
class UserApiTest extends TestCase
{
    public function testListUsersWithAuthentication(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertIsArray($data['data']);

        // Should contain our test user
        $this->assertGreaterThanOrEqual(1, count($data['data']));

        // Check pagination structure
        $pagination = $data['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
    }

    public function testListUsersWithoutAuthentication(): void
    {
        $request = $this->createRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testListUsersWithPagination(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users?page=1&limit=10');

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $pagination = $data['pagination'];
        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(10, $pagination['per_page']);
    }

    public function testListUsersWithFilters(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users?role=admin&status=active');

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);

        // All returned users should match filters
        foreach ($data['data'] as $user) {
            $this->assertSame('admin', $user['role']);
            $this->assertSame('active', $user['status']);
        }
    }

    public function testGetUserById(): void
    {
        // First get the test user ID
        $listRequest = $this->createAuthenticatedRequest('GET', '/api/users');
        $listResponse = $this->executeRequest($listRequest);
        $listData = $this->getJsonResponse($listResponse);
        $userId = $listData['data'][0]['id'];

        // Now get specific user
        $request = $this->createAuthenticatedRequest('GET', "/api/users/{$userId}");

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        $user = $data['data'];

        $this->assertSame($userId, $user['id']);
        $this->assertSame('admin@example.com', $user['email']);
        $this->assertSame('Admin User', $user['name']);
    }

    public function testGetUserByIdNotFound(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users/non-existent-id');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 404, 'USER_NOT_FOUND');
    }

    public function testGetUserWithoutAuthentication(): void
    {
        $request = $this->createRequest('GET', '/api/users/some-id');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testCreateUser(): void
    {
        $userData = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'NewPassword123',
            'role' => 'user',
        ];

        $request = $this->createAuthenticatedRequest('POST', '/api/users', null, $userData);

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 201, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('message', $data);

        $user = $data['data'];
        $this->assertSame($userData['email'], $user['email']);
        $this->assertSame($userData['name'], $user['name']);
        $this->assertSame($userData['role'], $user['role']);
        $this->assertArrayNotHasKey('password_hash', $user); // Should not expose password
    }

    public function testCreateUserWithInvalidData(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'name' => '',
            'password' => '123', // Too short
        ];

        $request = $this->createAuthenticatedRequest('POST', '/api/users', null, $userData);

        $response = $this->executeRequest($request);
        $data = $this->assertErrorResponse($response, 422, 'VALIDATION_ERROR');

        $this->assertArrayHasKey('details', $data['error']);
        $this->assertIsArray($data['error']['details']);
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $userData = [
            'email' => 'admin@example.com', // Already exists
            'name' => 'Duplicate User',
            'password' => 'Password123',
        ];

        $request = $this->createAuthenticatedRequest('POST', '/api/users', null, $userData);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 422, 'VALIDATION_ERROR');
    }

    public function testCreateUserWithoutAuthentication(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'Password123',
        ];

        $request = $this->createJsonRequest('POST', '/api/users', $userData);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 401, 'MISSING_TOKEN');
    }

    public function testUpdateUserNotImplemented(): void
    {
        $request = $this->createAuthenticatedRequest('PUT', '/api/users/some-id', null, [
            'name' => 'Updated Name',
        ]);

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 501, 'NOT_IMPLEMENTED');
    }

    public function testDeleteUserNotImplemented(): void
    {
        $request = $this->createAuthenticatedRequest('DELETE', '/api/users/some-id');

        $response = $this->executeRequest($request);
        $this->assertErrorResponse($response, 501, 'NOT_IMPLEMENTED');
    }

    public function testUserApiRequiresProperPermissions(): void
    {
        // This test assumes authorization middleware is working
        // In a real scenario, we'd create a user with limited permissions
        $request = $this->createAuthenticatedRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        // Admin user should have access
        $this->assertJsonResponse($response, 200, true);
    }

    public function testUserDataStructure(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users');

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $user = $data['data'][0];
        $expectedFields = [
            'id', 'email', 'name', 'role', 'status', 'email_verified',
            'last_login_at', 'login_count', 'is_active', 'is_admin',
            'is_editor', 'created_at', 'updated_at'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $user, "User should have field: {$field}");
        }

        // Sensitive fields should not be exposed
        $this->assertArrayNotHasKey('password_hash', $user);
        $this->assertArrayNotHasKey('password_reset_token', $user);
        $this->assertArrayNotHasKey('email_verification_token', $user);
    }

    public function testUserSearchFunctionality(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/api/users?search=admin');

        $response = $this->executeRequest($request);
        $data = $this->assertJsonResponse($response, 200, true);

        $this->assertArrayHasKey('data', $data);
        // Should find our admin user
        $this->assertGreaterThanOrEqual(1, count($data['data']));
    }
}
