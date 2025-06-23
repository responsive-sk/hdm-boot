<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Middleware;

use HdmBoot\Modules\Core\User\Domain\Entities\User;
use HdmBoot\Modules\Core\User\Domain\ValueObjects\UserId;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\Shared\Middleware\UserAuthenticationMiddleware;
use ResponsiveSk\Slim4Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Test UserAuthenticationMiddleware - samuelgfeller pattern.
 */
#[CoversClass(UserAuthenticationMiddleware::class)]
final class UserAuthenticationMiddlewareTest extends TestCase
{
    private UserAuthenticationMiddleware $middleware;
    private SessionInterface $session;
    private ResponseFactoryInterface $responseFactory;
    private UserService $userService;
    private LoggerInterface $logger;
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->createMock(SessionInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);

        // Create real UserService with mocked repository
        $repository = $this->createMock(UserRepositoryInterface::class);
        $this->userService = new UserService($repository);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new UserAuthenticationMiddleware(
            $this->session,
            $this->responseFactory,
            $this->userService,
            $this->logger
        );
    }

    #[Test]
    public function middlewareStartsSessionIfNotStarted(): void
    {
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(false);

        $this->session->expects($this->once())
            ->method('start');

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn(null);

        $this->setupRedirectResponse();

        $this->middleware->process($this->request, $this->handler);
    }

    #[Test]
    public function middlewareAllowsAccessForAuthenticatedUser(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $user = $this->createMockUser($userId);

        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn($userId);

        $this->userService->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($user);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    public function middlewareRedirectsUnauthenticatedUser(): void
    {
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn(null);

        $this->setupRedirectResponse();

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    public function middlewareRedirectsInactiveUser(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $user = $this->createMockUser($userId, 'inactive');

        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn($userId);

        $this->userService->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($user);

        $this->session->expects($this->once())
            ->method('destroy');

        $this->session->expects($this->once())
            ->method('start');

        $this->session->expects($this->once())
            ->method('regenerateId');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'User authentication failed - user not found or inactive',
                $this->arrayHasKey('user_id')
            );

        $this->setupRedirectResponse();

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    public function middlewareHandlesUserServiceException(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn($userId);

        $this->userService->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willThrowException(new \Exception('Database error'));

        $this->session->expects($this->once())
            ->method('destroy');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'User authentication error',
                $this->arrayHasKey('error')
            );

        $this->setupRedirectResponse();

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    public function middlewareReturnsJsonForApiRequests(): void
    {
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with('user_id')
            ->willReturn(null);

        $this->request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/api/users');

        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->stringContains('Authentication required'));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(401)
            ->willReturnSelf();

        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    private function createMockUser(string $id, string $status = 'active'): User
    {
        $user = $this->createMock(User::class);
        $user->method('getStatus')->willReturn($status);
        return $user;
    }

    private function setupRedirectResponse(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/profile');

        $this->request->method('getUri')->willReturn($uri);
        $this->request->method('getQueryParams')->willReturn([]);
        $this->request->method('getHeaderLine')->willReturn('text/html');

        $this->response->method('withHeader')->willReturnSelf();
        $this->response->method('withStatus')->willReturnSelf();

        $this->responseFactory->method('createResponse')->willReturn($this->response);

        $flash = $this->createMock(\Odan\Session\FlashInterface::class);
        $this->session->method('getFlash')->willReturn($flash);
    }
}
