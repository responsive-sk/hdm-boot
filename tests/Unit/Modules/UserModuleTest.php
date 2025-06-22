<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Modules;

use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\Tests\TestCase\ModuleTestCase;
use PDO;

/**
 * User Module Test.
 * 
 * Tests the User module in isolation with mocked dependencies.
 */
class UserModuleTest extends ModuleTestCase
{
    protected function getModuleName(): string
    {
        return 'User';
    }

    protected function getModuleDependencies(): array
    {
        return ['Database']; // User depends on Database module
    }

    protected function getAdditionalServices(): array
    {
        return [
            // Mock PDO for database operations
            PDO::class => function (): PDO {
                $pdo = new PDO('sqlite::memory:');
                
                // Create users table for testing
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
        ];
    }

    public function testModuleIsLoaded(): void
    {
        $this->assertModuleLoaded('User');
    }

    public function testModuleHasManifest(): void
    {
        $this->assertModuleHasManifest('User');
        
        $manifest = $this->getModuleManifest('User');
        $this->assertSame('User', $manifest->getName());
        $this->assertSame('1.0.0', $manifest->getVersion());
        $this->assertContains('Database', $manifest->getDependencies());
    }

    public function testUserServicesAreRegistered(): void
    {
        // Test that User services are registered
        $this->assertServiceRegistered(UserService::class);
        $this->assertServiceRegistered(UserRepositoryInterface::class);
    }

    public function testUserServiceCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(UserService::class, UserService::class);
        
        $userService = $this->getService(UserService::class);
        $this->assertInstanceOf(UserService::class, $userService);
    }

    public function testUserRepositoryCanBeCreated(): void
    {
        $this->assertServiceInstanceOf(UserRepositoryInterface::class, UserRepositoryInterface::class);
        
        $userRepository = $this->getService(UserRepositoryInterface::class);
        $this->assertInstanceOf(UserRepositoryInterface::class, $userRepository);
    }

    public function testUserServiceCanCreateUser(): void
    {
        $userService = $this->getService(UserService::class);
        
        $userData = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ];
        
        $user = $userService->createUser($userData);
        
        $this->assertIsArray($user);
        $this->assertSame('test@example.com', $user['email']);
        $this->assertSame('Test User', $user['name']);
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('created_at', $user);
    }

    public function testUserServiceCanFindUserByEmail(): void
    {
        $userService = $this->getService(UserService::class);
        
        // Create a test user first
        $userData = [
            'email' => 'findme@example.com',
            'name' => 'Find Me User',
            'password' => 'password123',
        ];
        
        $createdUser = $userService->createUser($userData);
        
        // Now find the user by email
        $foundUser = $userService->findUserByEmail('findme@example.com');
        
        $this->assertIsArray($foundUser);
        $this->assertSame($createdUser['id'], $foundUser['id']);
        $this->assertSame('findme@example.com', $foundUser['email']);
        $this->assertSame('Find Me User', $foundUser['name']);
    }

    public function testUserServiceCanAuthenticateUser(): void
    {
        $userService = $this->getService(UserService::class);
        
        // Create a test user first
        $userData = [
            'email' => 'auth@example.com',
            'name' => 'Auth User',
            'password' => 'password123',
        ];
        
        $userService->createUser($userData);
        
        // Test authentication with correct credentials
        $authenticatedUser = $userService->authenticate('auth@example.com', 'password123');
        
        $this->assertIsArray($authenticatedUser);
        $this->assertSame('auth@example.com', $authenticatedUser['email']);
        $this->assertSame('Auth User', $authenticatedUser['name']);
        
        // Test authentication with wrong password
        $failedAuth = $userService->authenticate('auth@example.com', 'wrongpassword');
        $this->assertNull($failedAuth);
    }

    public function testModuleManifestMetadata(): void
    {
        $manifest = $this->getModuleManifest('User');
        
        $this->assertStringContainsString('User management', $manifest->getDescription());
        $this->assertContains('user', $manifest->getTags());
        $this->assertContains('profile', $manifest->getTags());
        $this->assertContains('management', $manifest->getTags());
        
        $provides = $manifest->getProvides();
        $this->assertContains('user-management', $provides);
        $this->assertContains('user-repository', $provides);
        $this->assertContains('user-services', $provides);
    }

    public function testModuleDependencies(): void
    {
        $manifest = $this->getModuleManifest('User');
        $dependencies = $manifest->getDependencies();
        
        $this->assertContains('Database', $dependencies);
        
        // Verify that Database module is also loaded
        $this->assertModuleLoaded('Database');
    }
}
