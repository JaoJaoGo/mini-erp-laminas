<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\User;
use Application\Service\AuthService;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private EntityManager $entityManager;
    private Container $session;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->session = $this->createMock(Container::class);
        $this->authService = new AuthService($this->entityManager, $this->session);
    }

    public function testLoginStoresSessionAndReturnsTrueForValidUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(true);
        $user->method('verifyPassword')->with('secret')->willReturn(true);
        $user->method('getId')->willReturn(42);
        $user->method('getName')->willReturn('Test User');
        $user->method('getEmail')->willReturn('test@example.com');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $expected = [
            ['userId', 42],
            ['userName', 'Test User'],
            ['userEmail', 'test@example.com'],
        ];
        $calls = [];

        $this->session->expects(self::exactly(3))
            ->method('offsetSet')
            ->willReturnCallback(static function ($key, $value) use (&$calls): void {
                $calls[] = [$key, $value];
            });

        self::assertTrue($this->authService->login('test@example.com', 'secret'));
        self::assertSame($expected, $calls);
    }

    public function testLoginReturnsFalseWhenUserCannotBeFound(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'missing@example.com'])
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertFalse($this->authService->login('missing@example.com', 'secret'));
    }

    public function testLoginReturnsFalseForInactiveUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(false);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertFalse($this->authService->login('test@example.com', 'secret'));
    }

    public function testLoginReturnsFalseWhenPasswordIsInvalid(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(true);
        $user->method('verifyPassword')->with('wrong')->willReturn(false);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertFalse($this->authService->login('test@example.com', 'wrong'));
    }

    public function testLogoutClearsAuthStorage(): void
    {
        $this->session->expects(self::exactly(3))
            ->method('offsetUnset');

        $this->authService->logout();
    }

    public function testGetAuthenticatedUserIdReturnsNullWhenMissing(): void
    {
        $this->session->method('offsetGet')
            ->with('userId')
            ->willReturn(null);

        self::assertNull($this->authService->getAuthenticatedUserId());
    }

    public function testIsAuthenticatedReturnsTrueWhenUserIdExists(): void
    {
        $this->session->method('offsetGet')
            ->with('userId')
            ->willReturn(10);

        self::assertTrue($this->authService->isAuthenticated());
    }

    public function testGetAuthenticatedUserReturnsUserWhenSessionHasId(): void
    {
        $user = $this->createMock(User::class);
        $this->session->method('offsetGet')
            ->with('userId')
            ->willReturn(42);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(User::class, 42)
            ->willReturn($user);

        self::assertSame($user, $this->authService->getAuthenticatedUser());
    }
}
