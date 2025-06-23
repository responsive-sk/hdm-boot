<?php

declare(strict_types=1);

namespace HdmBoot\Tests;

use DI\Container;
use HdmBoot\Boot\App;
use HdmBoot\Shared\Services\DatabaseManager;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Base Test Case for MVA Bootstrap Application.
 *
 * Provides common functionality for all tests including:
 * - Application setup
 * - Database management
 * - HTTP request/response helpers
 * - Authentication helpers
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected App $app;
    protected SlimApp $slimApp;
    protected Container $container;
    protected DatabaseManager $databaseManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize application
        $this->app = new App(__DIR__ . '/..');
        $this->app->initialize();
        $this->slimApp = $this->app->getSlimApp();
        $this->container = $this->slimApp->getContainer();

        // Setup in-memory database
        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanupDatabase();
        parent::tearDown();
    }

    /**
     * Setup in-memory database for testing.
     */
    protected function setupDatabase(): void
    {
        $this->databaseManager = $this->container->get(DatabaseManager::class);

        // Create users table
        $this->databaseManager->executeRawSql('
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                email TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT "user",
                status TEXT NOT NULL DEFAULT "active",
                email_verified INTEGER NOT NULL DEFAULT 0,
                email_verification_token TEXT,
                password_reset_token TEXT,
                password_reset_expires TEXT,
                last_login_at TEXT,
                login_count INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        // Create security tables
        $this->databaseManager->executeRawSql('
            CREATE TABLE IF NOT EXISTS security_login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT,
                ip_address TEXT,
                user_agent TEXT,
                success INTEGER NOT NULL DEFAULT 0,
                attempted_at TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime("now"))
            )
        ');

        // Insert test admin user
        $this->createTestUser();
    }

    /**
     * Create test user for authentication tests.
     */
    protected function createTestUser(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'admin@example.com';
        $passwordHash = password_hash('Password123', PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        $this->databaseManager->executeRawSql("
            INSERT OR REPLACE INTO users (
                id, email, name, password_hash, role, status,
                email_verified, created_at, updated_at
            ) VALUES (
                '{$userId}', '{$email}', 'Admin User', '{$passwordHash}', 'admin', 'active',
                1, '{$now}', '{$now}'
            )
        ");
    }

    /**
     * Clean up database after test.
     */
    protected function cleanupDatabase(): void
    {
        // In-memory database is automatically cleaned up
    }

    /**
     * Create HTTP request for testing.
     */
    protected function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        ?string $body = null
    ): ServerRequestInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $request->getBody()->write($body);
            $request->getBody()->rewind();
        }

        return $request;
    }

    /**
     * Create JSON request for testing.
     */
    protected function createJsonRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): ServerRequestInterface {
        $headers['Content-Type'] = 'application/json';
        $body = json_encode($data) ?: '{}';

        return $this->createRequest($method, $uri, $headers, $body);
    }

    /**
     * Execute request through Slim application.
     */
    protected function executeRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->slimApp->handle($request);
    }

    /**
     * Get JSON response data.
     */
    protected function getJsonResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Login and get JWT token for testing.
     */
    protected function loginAndGetToken(string $email = 'admin@example.com', string $password = 'Password123'): string
    {
        $request = $this->createJsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response = $this->executeRequest($request);
        $data = $this->getJsonResponse($response);

        if (!isset($data['data']['token'])) {
            $this->fail('Failed to get authentication token: ' . json_encode($data));
        }

        return $data['data']['token'];
    }

    /**
     * Create authenticated request with JWT token.
     */
    protected function createAuthenticatedRequest(
        string $method,
        string $uri,
        ?string $token = null,
        array $data = []
    ): ServerRequestInterface {
        $token = $token ?? $this->loginAndGetToken();
        $headers = ['Authorization' => 'Bearer ' . $token];

        if (!empty($data)) {
            return $this->createJsonRequest($method, $uri, $data, $headers);
        }

        return $this->createRequest($method, $uri, $headers);
    }

    /**
     * Assert JSON response structure.
     */
    protected function assertJsonResponse(
        ResponseInterface $response,
        int $expectedStatus = 200,
        bool $expectedSuccess = true
    ): array {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $data = $this->getJsonResponse($response);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertSame($expectedSuccess, $data['success']);

        return $data;
    }

    /**
     * Assert error response.
     */
    protected function assertErrorResponse(
        ResponseInterface $response,
        int $expectedStatus,
        string $expectedCode = null
    ): array {
        $data = $this->assertJsonResponse($response, $expectedStatus, false);
        $this->assertArrayHasKey('error', $data);

        if ($expectedCode !== null) {
            $this->assertArrayHasKey('code', $data['error']);
            $this->assertSame($expectedCode, $data['error']['code']);
        }

        return $data;
    }
}
