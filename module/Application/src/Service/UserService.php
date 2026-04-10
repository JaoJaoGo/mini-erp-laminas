<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Serviço responsável por gerenciar usuários no sistema.
 *
 * O UserService fornece operações de verificação de existência de e-mail e criação
 * de usuários, utilizando o EntityManager para persistência.
 *
 * Métodos disponíveis:
 * - emailExists(string $email): bool
 * - create(string $name, string $email, string $password): User
 */
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