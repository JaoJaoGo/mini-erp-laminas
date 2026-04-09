<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\User;
use Application\Service\UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->service = new UserService($this->entityManager);
    }

    public function testEmailExistsReturnsTrueWhenUserFound(): void
    {
        $user = new User();
        $repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($user);

        self::assertTrue($this->service->emailExists('EXISTING@EXAMPLE.COM'));
    }

    public function testEmailExistsReturnsFalseWhenUserNotFound(): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        self::assertFalse($this->service->emailExists('nonexistent@example.com'));
    }

    public function testCreatePersistsUserAndFlushes(): void
    {
        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(User::class));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $user = $this->service->create('João Silva', 'joao@example.com', 'hashed_password_123');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('João Silva', $user->getName());
        self::assertSame('joao@example.com', $user->getEmail());
        self::assertTrue($user->isActive());
    }

    public function testEmailExistsTrimsAndLowercasesEmail(): void
    {
        $user = new User();
        $repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        self::assertTrue($this->service->emailExists('  TEST@EXAMPLE.COM  '));
    }
}
