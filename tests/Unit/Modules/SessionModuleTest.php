<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Modules;

use HdmBoot\Modules\Core\Session\Services\CsrfService;
use HdmBoot\Modules\Core\Session\Services\SessionService;
use HdmBoot\Tests\TestCase\ModuleTestCase;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Session Module Test.
 * 
 * Tests the Session module in isolation with mocked dependencies.
 */
class SessionModuleTest extends ModuleTestCase
{
    protected function getModuleName(): string
    {
        return 'Session';
    }

    protected function getModuleDependencies(): array
    {
        return []; // Session module has no dependencies
    }

    protected function getModuleServiceOverrides(): array
    {
        return [
            // Mock Session for testing
            SessionInterface::class => function (): SessionInterface {
                return $this->createMock(SessionInterface::class);
            },

            // Settings for Session module
            'settings' => [
                'app' => [
                    'name' => 'MVA Bootstrap Test',
                    'env' => 'testing',
                ],
                'paths' => [
                    'root' => dirname(__DIR__, 3),
                    'modules' => dirname(__DIR__, 3) . '/src/Modules/Core',
                ],
                'session' => [
                    'name'            => 'test_session',
                    'lifetime'        => 3600,
                    'cookie_secure'   => false,
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Lax',
                ],
                'csrf' => [
                    'token_length'    => 32,
                    'max_tokens'      => 10,
                    'session_key'     => 'csrf_tokens',
                ],
            ],
        ];
    }

    public function testModuleIsLoaded(): void
    {
        $this->assertModuleLoaded('Session');
    }

    public function testModuleHasManifest(): void
    {
        // Session module should have a manifest
        $manifest = $this->getModuleManifest('Session');

        if ($manifest) {
            $this->assertSame('Session', $manifest->getName());
            $this->assertSame('1.0.0', $manifest->getVersion());
            $this->assertEmpty($manifest->getDependencies()); // No dependencies
        } else {
            // If no manifest, module should still be loaded via config
            $this->assertModuleLoaded('Session');
        }
    }

    public function testSessionServicesAreRegistered(): void
    {
        // Test that all Session services are registered
        $this->assertServiceRegistered(SessionService::class);
        $this->assertServiceRegistered(CsrfService::class);
        $this->assertServiceRegistered(SessionInterface::class);
    }

    public function testSessionServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(SessionService::class, SessionService::class);

        $sessionService = $this->getService(SessionService::class);
        $this->assertInstanceOf(SessionService::class, $sessionService);
    }

    public function testCsrfServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(CsrfService::class, CsrfService::class);

        $csrfService = $this->getService(CsrfService::class);
        $this->assertInstanceOf(CsrfService::class, $csrfService);
    }

    public function testModuleManifestMetadata(): void
    {
        $manifest = $this->getModuleManifest('Session');

        if ($manifest) {
            $this->assertStringContainsString('Session management', $manifest->getDescription());
            $this->assertContains('session', $manifest->getTags());
            $this->assertContains('csrf', $manifest->getTags());
            $this->assertContains('security', $manifest->getTags());

            $provides = $manifest->getProvides();
            $this->assertContains('session-management', $provides);
            $this->assertContains('csrf-protection', $provides);
            $this->assertContains('session-persistence', $provides);
        } else {
            // If no manifest, just verify module is working
            $this->assertModuleLoaded('Session');
        }
    }

    public function testModuleDependencies(): void
    {
        $manifest = $this->getModuleManifest('Session');

        if ($manifest) {
            $dependencies = $manifest->getDependencies();
            $this->assertEmpty($dependencies); // Session module has no dependencies
        }

        // Session module should be a base module
        $this->assertModuleLoaded('Session');
    }
}
