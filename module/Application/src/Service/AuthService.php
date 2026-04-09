<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\User;
use Doctrine\ORM\EntityManager;
use Laminas\Session\Container;

/**
 * Serviço responsável por gerenciar a autenticação dos usuários no sistema. Ele utiliza o EntityManager para acessar o banco de dados e verificar as credenciais do usuário, e o Container de sessão para armazenar as informações do usuário autenticado.
 * O AuthService fornece métodos para realizar o login, logout, verificar se um usuário está autenticado, e obter as informações do usuário autenticado. Durante o processo de login, ele verifica se
 * o usuário existe, se está ativo, e se a senha fornecida é válida. Se a autenticação for bem-sucedida, ele armazena as informações do usuário na sessão para manter o estado de autenticação durante a navegação do usuário no sistema.
 * O AuthService é utilizado pelo AuthController para processar as tentativas de login e logout dos usuários, garantindo que apenas usuários autenticados possam acessar áreas restritas do sistema.
 * 
 * Métodos disponíveis:
 * - login(string $email, string $password): bool - Realiza o login do usuário, verificando as credenciais e armazenando as informações na sessão.
 * - logout(): void - Realiza o logout do usuário, limpando as informações da sessão.
 * - isAuthenticated(): bool - Verifica se um usuário está autenticado.
 * - getAuthenticatedUserId(): ?int - Retorna o ID do usuário autenticado, ou null se não houver um usuário autenticado.
 * 
 * O AuthService é uma parte essencial do processo de autenticação, garantindo que os usuários possam acessar o sistema de forma segura e que suas informações sejam gerenciadas corretamente durante a sessão. Ele é projetado para ser reutilizável e fácil de integrar com outros componentes do sistema, como controladores e formulários de autenticação.
 */
class AuthService
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly Container $session,
    ) { }

    public function login(string $email, string $password): bool
    {
        /** @var User|null $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'email' => mb_strtolower(trim($email)),
            ]);

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->isActive()) {
            return false;
        }

        if (!$user->verifyPassword($password)) {
            return false;
        }

        $this->session->offsetSet('userId', $user->getId());
        $this->session->offsetSet('userName', $user->getName());
        $this->session->offsetSet('userEmail', $user->getEmail());

        return true;
    }

    public function logout(): void
    {
        $this->session->offsetUnset('userId');
        $this->session->offsetUnset('userName');
        $this->session->offsetUnset('userEmail');
    }

    public function isAuthenticated(): bool
    {
        return !empty($this->session->offsetGet('userId'));
    }

    public function getAuthenticatedUserId(): ?int
    {
        $userId = $this->session->offsetGet('userId');

        if ($userId === null || $userId === '') {
            return null;
        }

        return (int) $userId;
    }

    public function getAuthenticatedUser(): ?User
    {
        $userId = $this->getAuthenticatedUserId();

        if ($userId === null) {
            return null;
        }

        $user = $this->entityManager->find(User::class, $userId);

        return $user instanceof User ? $user : null;
    }
}