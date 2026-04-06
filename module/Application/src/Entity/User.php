<?php

declare(strict_types=1);

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa um usuário do sistema.
 * Cada usuário tem um nome, email, senha e status de atividade.
 * 
 * Propriedades:
 * - id: Identificador único do usuário (inteiro, auto-incrementado).
 * - name: Nome do usuário (string, máximo de 120 caracteres).
 * - email: Email do usuário (string, máximo de 180 caracteres, único).
 * - password: Senha do usuário (string, máximo de 255 caracteres).
 * - isActive: Indica se o usuário está ativo (booleano, padrão true).
 * - createdAt: Data e hora de criação do usuário (DateTimeImmutable).
 * - updatedAt: Data e hora da última atualização do usuário (DateTimeImmutable).
 * 
 * Métodos:
 * - getId(): Retorna o ID do usuário.
 * - getName(): Retorna o nome do usuário.
 * - setName(string $name): Define o nome do usuário.
 * - getEmail(): Retorna o email do usuário.
 * - setEmail(string $email): Define o email do usuário.
 * - getPassword(): Retorna a senha do usuário.
 * - setPassword(string $password): Define a senha do usuário.
 * - setPasswordHash(string $plainPassword): Define a senha do usuário a partir de uma senha em texto plano, aplicando hash.
 * - verifyPassword(string $plainPassword): Verifica se a senha em texto plano corresponde à senha armazenada (hash).
 * - isActive(): Retorna se o usuário está ativo.
 * - setIsActive(bool $isActive): Define se o usuário está ativo.
 * - getCreatedAt(): Retorna a data de criação do usuário.
 * - getUpdatedAt(): Retorna a data da última atualização do usuário.
 * 
 * Observações:
 * - A classe utiliza anotações do Doctrine ORM para mapear a entidade e suas propriedades para o banco de dados.
 * - Os métodos onPrePersist e onPreUpdate são usados para definir automaticamente as datas de criação e atualização do usuário.
 * - A classe é projetada para ser usada em um contexto de aplicação que gerencia usuários, como um sistema de autenticação e autorização.
 * - A classe é parte do namespace Application\Entity, indicando que é uma entidade de domínio dentro da aplicação.
 * - A classe é marcada com #[ORM\HasLifecycleCallbacks] para permitir a execução de métodos específicos durante o ciclo de vida da entidade, como antes de persistir ou atualizar.
 * 
 * @package Application\Entity
 * @author João Víctor Guedes Carrijo <jvgcarrijo@gmail.com>
 * @version 1.0
 * @since 2024-04-06
 * 
 * @ORM\Entity
 * @ORM\Table(name="users")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
    public function setPasswordHash(string $plainPassword): self
    {
        $this->password = password_hash($plainPassword, PASSWORD_DEFAULT);

        return $this;
    }
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}