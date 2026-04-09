<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\User;
use Doctrine\ORM\EntityManager;

class UserService
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) { }

    public function emailExists(string $email): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => mb_strtolower(trim($email)),
        ]);

        return $user instanceof User;
    }

    public function create(string $name, string $email, string $password): User
    {
        $user = new User();

        $user->setName($name);
        $user->setEmail($email);
        $user->setPasswordHash($password);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}