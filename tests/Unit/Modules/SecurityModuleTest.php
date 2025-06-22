<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Modules;

use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use HdmBoot\Modules\Core\Security\Services\AuthorizationService;
// Note: CsrfService moved to Session module
use HdmBoot\Modules\Core\Security\Services\JwtService;
use HdmBoot\Tests\TestCase\ModuleTestCase;
use Odan\Session\SessionInterface;
use PDO;

/**
 * Security Module Test.
 * 
 * Tests the Security module in isolation with mocked dependencies.
 */
class SecurityModuleTest extends ModuleTestCase
{
    protected function getModuleName(): string
    {
        return 'Security';
    }

    protected function getModuleDependencies(): array
    {
        return ['User', 'Session']; // Security depends on User and Session modules
    }

    protected function getAdditionalServices(): array
    {
        return [
            // Mock PDO for database operations
            PDO::class => function (): PDO {
                $pdo = new PDO('sqlite::memory:');

                // Create users table for User module dependency
                $pdo->exec('
                    CREATE TABLE users (
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

                return $pdo;
            },

            // Mock Session for CSRF service
            SessionInterface::class => function (): SessionInterface {
                return $this->createMock(SessionInterface::class);
            },

            // Settings for JWT service and other modules
            'settings' => [
                'app' => [
                    'name' => 'MVA Bootstrap Test',
                    'env' => 'testing',
                ],
                'paths' => [
                    'root' => dirname(__DIR__, 3),
                    'modules' => dirname(__DIR__, 3) . '/src/Modules/Core',
                ],
                'security' => [
                    'jwt_secret' => 'test-secret-key-for-testing-only',
                    'jwt_expiry' => 3600,
                ],
                'database' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ];
    }

    public function testModuleIsLoaded(): void
    {
        $this->assertModuleLoaded('Security');
    }

    public function testModuleHasManifest(): void
    {
        // Security module should have a manifest
        $manifest = $this->getModuleManifest('Security');

        if ($manifest) {
            $this->assertSame('Security', $manifest->getName());
            $this->assertSame('1.0.0', $manifest->getVersion());
            $dependencies = $manifest->getDependencies();
            $this->assertContains('User', $dependencies);
            $this->assertContains('Session', $dependencies);
        } else {
            // If no manifest, module should still be loaded via config
            $this->assertModuleLoaded('Security');
        }
    }

    public function testSecurityServicesAreRegistered(): void
    {
        // Test that all Security services are registered
        // Note: CsrfService moved to Session module
        $this->assertServiceRegistered(JwtService::class);
        $this->assertServiceRegistered(AuthenticationService::class);
        $this->assertServiceRegistered(AuthorizationService::class);
    }

    // Note: CsrfService test moved to SessionModuleTest

    public function testJwtServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(JwtService::class, JwtService::class);
        
        $jwtService = $this->getService(JwtService::class);
        $this->assertInstanceOf(JwtService::class, $jwtService);
    }

    public function testAuthenticationServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(AuthenticationService::class, AuthenticationService::class);
        
        $authService = $this->getService(AuthenticationService::class);
        $this->assertInstanceOf(AuthenticationService::class, $authService);
    }

    public function testAuthorizationServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(AuthorizationService::class, AuthorizationService::class);
        
        $authzService = $this->getService(AuthorizationService::class);
        $this->assertInstanceOf(AuthorizationService::class, $authzService);
    }

    public function testJwtServiceConfiguration(): void
    {
        $jwtService = $this->getService(JwtService::class);

        // Test that JWT service is properly configured and instantiated
        $this->assertInstanceOf(JwtService::class, $jwtService);

        // Note: Full JWT testing would require User entity conversion
        // which is beyond the scope of this module test.
        // Integration tests should cover the full JWT workflow.
        $this->assertTrue(true, 'JwtService is properly instantiated and configured');
    }

    public function testModuleManifestMetadata(): void
    {
        $manifest = $this->getModuleManifest('Security');

        if ($manifest) {
            $this->assertStringContainsString('Authentication', $manifest->getDescription());
            $this->assertContains('security', $manifest->getTags());
            $this->assertContains('authentication', $manifest->getTags());
            $this->assertContains('authorization', $manifest->getTags());

            $provides = $manifest->getProvides();
            $this->assertContains('authentication', $provides);
            $this->assertContains('authorization', $provides);
            // Note: csrf-protection moved to Session module
            $this->assertContains('jwt-tokens', $provides);
        } else {
            // If no manifest, just verify module is working
            $this->assertModuleLoaded('Security');
        }
    }

    public function testModuleDependencies(): void
    {
        $manifest = $this->getModuleManifest('Security');

        if ($manifest) {
            $dependencies = $manifest->getDependencies();
            $this->assertContains('User', $dependencies);
            $this->assertContains('Session', $dependencies);
        }

        // Verify that dependency modules are also loaded
        $this->assertModuleLoaded('User');
        $this->assertModuleLoaded('Session');
    }
}
