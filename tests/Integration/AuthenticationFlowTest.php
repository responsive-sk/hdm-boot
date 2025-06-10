<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Integration;

use MvaBootstrap\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration test for complete authentication flow.
 * Tests the enterprise authentication system end-to-end.
 */
#[CoversNothing]
final class AuthenticationFlowTest extends TestCase
{
    #[Test]
    public function completeAuthenticationFlowWorks(): void
    {
        // 1. Test unauthenticated access to protected page
        $request = $this->createRequest('GET', '/profile');
        $response = $this->executeRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContains('/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function loginPageLoadsCorrectly(): void
    {
        $request = $this->createRequest('GET', '/login');
        $response = $this->executeRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContains('text/html', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContains('Login', $body);
        $this->assertStringContains('csrf_token', $body);
    }

    #[Test]
    public function loginWithValidCredentialsWorks(): void
    {
        // 1. Get login page to extract CSRF token
        $loginResponse = $this->createRequest('GET', '/login');
        $loginBody = (string) $loginResponse->getBody();
        
        // Extract CSRF token
        preg_match('/name="csrf_token" value="([^"]+)"/', $loginBody, $matches);
        $this->assertNotEmpty($matches, 'CSRF token not found in login form');
        $this->assertArrayHasKey(1, $matches, 'CSRF token value not captured');
        $csrfToken = $matches[1];

        // 2. Submit login form
        $response = $this->createRequest('POST', '/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'csrf_token' => $csrfToken,
        ]);

        // Should redirect to profile
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContains('/profile', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function loginWithInvalidCredentialsFails(): void
    {
        // 1. Get CSRF token
        $loginResponse = $this->createRequest('GET', '/login');
        $loginBody = (string) $loginResponse->getBody();
        
        preg_match('/name="csrf_token" value="([^"]+)"/', $loginBody, $matches);
        $this->assertArrayHasKey(1, $matches, 'CSRF token not found in invalid credentials test');
        $csrfToken = $matches[1];

        // 2. Submit with invalid credentials
        $response = $this->createRequest('POST', '/login', [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword',
            'csrf_token' => $csrfToken,
        ]);

        // Should stay on login page
        $this->assertSame(200, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        $this->assertStringContains('Invalid credentials', $body);
    }

    #[Test]
    public function profilePageRequiresAuthentication(): void
    {
        // Direct access to profile should redirect to login
        $response = $this->createRequest('GET', '/profile');
        
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContains('/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function authenticatedUserCanAccessProfile(): void
    {
        // 1. Login first
        $this->loginAsAdmin();

        // 2. Access profile page
        $response = $this->createRequest('GET', '/profile');
        
        $this->assertSame(200, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        $this->assertStringContains('User Profile', $body);
        $this->assertStringContains('admin@example.com', $body);
        $this->assertStringContains('Enterprise Architecture', $body);
    }

    #[Test]
    public function sessionPersistsAcrossRequests(): void
    {
        // 1. Login
        $this->loginAsAdmin();

        // 2. Multiple requests should work without re-authentication
        $response1 = $this->createRequest('GET', '/profile');
        $this->assertSame(200, $response1->getStatusCode());

        $response2 = $this->createRequest('GET', '/profile');
        $this->assertSame(200, $response2->getStatusCode());

        // Both should show user data
        $body1 = (string) $response1->getBody();
        $body2 = (string) $response2->getBody();
        
        $this->assertStringContains('admin@example.com', $body1);
        $this->assertStringContains('admin@example.com', $body2);
    }

    #[Test]
    public function logoutClearsSession(): void
    {
        // 1. Login first
        $this->loginAsAdmin();

        // 2. Verify we can access profile
        $profileResponse = $this->createRequest('GET', '/profile');
        $this->assertSame(200, $profileResponse->getStatusCode());

        // 3. Logout
        $logoutResponse = $this->createRequest('POST', '/logout');
        $this->assertSame(302, $logoutResponse->getStatusCode());

        // 4. Try to access profile again - should redirect to login
        $profileAfterLogout = $this->createRequest('GET', '/profile');
        $this->assertSame(302, $profileAfterLogout->getStatusCode());
        $this->assertStringContains('/login', $profileAfterLogout->getHeaderLine('Location'));
    }

    #[Test]
    public function csrfProtectionWorks(): void
    {
        // Try to login without CSRF token
        $response = $this->createRequest('POST', '/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
            // No CSRF token
        ]);

        // Should fail
        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function enterpriseLoggingWorks(): void
    {
        // Login should generate logs
        $this->loginAsAdmin();

        // Check that log files exist and contain authentication events
        $logDir = $this->getContainer()->get(\MvaBootstrap\Shared\Services\Paths::class)->base() . '/logs';
        
        $this->assertDirectoryExists($logDir);
        
        // Should have app log with authentication events
        $appLogFiles = glob($logDir . '/debug-app.log');
        $this->assertNotEmpty($appLogFiles, 'App log file should exist');
        
        if (!empty($appLogFiles)) {
            $logContent = file_get_contents($appLogFiles[0]);
            $this->assertStringContains('User authenticated successfully', $logContent);
        }
    }

    /**
     * Helper method to login as admin user.
     */
    private function loginAsAdmin(): void
    {
        // Get CSRF token
        $loginResponse = $this->createRequest('GET', '/login');
        $loginBody = (string) $loginResponse->getBody();
        
        preg_match('/name="csrf_token" value="([^"]+)"/', $loginBody, $matches);
        $this->assertArrayHasKey(1, $matches, 'CSRF token not found in helper method');
        $csrfToken = $matches[1];

        // Submit login
        $response = $this->createRequest('POST', '/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'csrf_token' => $csrfToken,
        ]);

        // Verify login succeeded
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContains('/profile', $response->getHeaderLine('Location'));
    }
}
