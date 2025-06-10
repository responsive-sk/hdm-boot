<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Unit\Services;

use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test UserService - Enterprise User Management.
 */
#[CoversClass(UserService::class)]
final class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->userService = new UserService($this->repository);
    }

    #[Test]
    public function serviceCanCreateUser(): void
    {
        $email = 'test@example.com';
        $name = 'Test User';
        $password = 'Password123';
        $role = 'user';

        // Mock repository to return false for email exists check
        $this->repository->expects($this->once())
            ->method('emailExists')
            ->with($email)
            ->willReturn(false);

        // Mock repository save method
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $user = $this->userService->createUser($email, $name, $password, $role);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($name, $user->getName());
        $this->assertSame($role, $user->getRole());
        $this->assertTrue($user->verifyPassword($password));
    }

    #[Test]
    public function serviceThrowsExceptionForDuplicateEmail(): void
    {
        $email = 'existing@example.com';
        $name = 'Test User';
        $password = 'Password123';

        // Mock repository to return true for email exists check
        $this->repository->expects($this->once())
            ->method('emailExists')
            ->with($email)
            ->willReturn(true);

        // Save should not be called
        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email address is already in use');

        $this->userService->createUser($email, $name, $password);
    }

    #[Test]
    public function serviceCanGetUserById(): void
    {
        $userId = UserId::generate();
        $user = $this->createMockUser($userId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->userService->getUserById($userId->toString());

        $this->assertSame($user, $result);
    }

    #[Test]
    public function serviceReturnsNullForInvalidUserId(): void
    {
        $invalidId = 'invalid-uuid';

        // Repository should not be called for invalid UUID
        $this->repository->expects($this->never())
            ->method('findById');

        $result = $this->userService->getUserById($invalidId);

        $this->assertNull($result);
    }

    #[Test]
    public function serviceCanGetUserByEmail(): void
    {
        $email = 'test@example.com';
        $user = $this->createMockUser();

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->userService->getUserByEmail($email);

        $this->assertSame($user, $result);
    }

    #[Test]
    public function serviceCanAuthenticateUser(): void
    {
        $email = 'test@example.com';
        $password = 'Password123';
        $user = $this->createMockUser();

        // Mock user methods
        $user->method('verifyPassword')->with($password)->willReturn(true);
        $user->method('isActive')->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $result = $this->userService->authenticate($email, $password);

        $this->assertSame($user, $result);
    }

    #[Test]
    public function serviceThrowsExceptionForInvalidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'WrongPassword';

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->userService->authenticate($email, $password);
    }

    #[Test]
    public function serviceThrowsExceptionForInactiveUser(): void
    {
        $email = 'test@example.com';
        $password = 'Password123';
        $user = $this->createMockUser();

        // Mock user as inactive
        $user->method('isActive')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User account is not active');

        $this->userService->authenticate($email, $password);
    }

    #[Test]
    public function serviceValidatesUserInput(): void
    {
        // Test empty email
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required');

        $this->userService->createUser('', 'Test User', 'Password123');
    }

    #[Test]
    public function serviceValidatesEmailFormat(): void
    {
        $this->repository->method('emailExists')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->userService->createUser('invalid-email', 'Test User', 'Password123');
    }

    #[Test]
    public function serviceValidatesPasswordStrength(): void
    {
        $this->repository->method('emailExists')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');

        $this->userService->createUser('test@example.com', 'Test User', 'PASSWORD123');
    }

    #[Test]
    public function serviceHasPermissionChecks(): void
    {
        $adminUser = $this->createMockUser();
        $adminUser->method('isAdmin')->willReturn(true);
        $adminUser->method('isEditor')->willReturn(false);

        $this->assertTrue($this->userService->hasPermission($adminUser, 'admin.access'));
        $this->assertTrue($this->userService->hasPermission($adminUser, 'user.delete'));
        $this->assertTrue($this->userService->hasPermission($adminUser, 'user.manage'));

        $regularUser = $this->createMockUser();
        $regularUser->method('isAdmin')->willReturn(false);
        $regularUser->method('isEditor')->willReturn(false);

        $this->assertTrue($this->userService->hasPermission($regularUser, 'user.view'));
        $this->assertFalse($this->userService->hasPermission($regularUser, 'admin.access'));
        $this->assertFalse($this->userService->hasPermission($regularUser, 'user.delete'));
    }

    /**
     * Create mock user for testing.
     */
    private function createMockUser(?UserId $userId = null): User
    {
        $user = $this->createMock(User::class);
        
        if ($userId !== null) {
            $user->method('getId')->willReturn($userId);
        }
        
        return $user;
    }
}
