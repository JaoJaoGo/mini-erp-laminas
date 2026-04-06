<?php

declare(strict_types=1);

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa um produto.
 * Cada produto pertence a uma única categoria, e cada categoria pode conter vários produtos.
 * 
 * Propriedades:
 * - id: Identificador único do produto (inteiro, auto-incrementado).
 * - name: Nome do produto (string, máximo de 150 caracteres).
 * - description: Descrição do produto (string, opcional).
 * - price: Preço do produto (decimal, precisão de 10 dígitos e escala de 2 dígitos).
 * - stock: Quantidade em estoque do produto (intero).
 * - isActive: Indica se o produto está ativo (booleano, padrão true).
 * - createdAt: Data e hora de criação do produto (DateTimeImmutable).
 * - updatedAt: Data e hora da última atualização do produto (DateTimeImmutable).
 * - category: Categoria à qual o produto pertence (ManyToOne).
 * 
 * Métodos:
 * - getId(): Retorna o ID do produto.
 * - getName(): Retorna o nome do produto.
 * - setName(string $name): Define o nome do produto.
 * - getDescription(): Retorna a descrição do produto.
 * - setDescription(?string $description): Define a descrição do produto.
 * - getPrice(): Retorna o preço do produto.
 * - setPrice(string $price): Define o preço do produto.
 * - getStock(): Retorna a quantidade em estoque do produto.
 * - setStock(int $stock): Define a quantidade em estoque do produto.
 * - isActive(): Retorna se o produto está ativo.
 * - setIsActive(bool $isActive): Define se o produto está ativo.
 * - getCreatedAt(): Retorna a data de criação do produto.
 * - getUpdatedAt(): Retorna a data da última atualização do produto.
 * - getCategory(): Retorna a categoria do produto.
 * - setCategory(?Category $category): Define a categoria do produto.
 * 
 * Observações:
 * - A classe utiliza anotações do Doctrine ORM para mapear a entidade e suas propriedades para o banco de dados.
 * - Os métodos onPrePersist e onPreUpdate são usados para definir automaticamente as datas de criação e atualização do produto.
 * - A relação entre Product e Category é bidirecional, permitindo acesso fácil à categoria de um produto e aos produtos de uma categoria. 
 * - A classe é projetada para ser usada em um contexto de aplicação que gerencia produtos e categorias, como um sistema de e-commerce.
 * - A classe é parte do namespace Application\Entity, indicando que é uma entidade de domínio dentro da aplicação.
 * - A classe é marcada com #[ORM\HasLifecycleCallbacks] para permitir a execução de métodos específicos durante o ciclo de vida da entidade, como antes de persistir ou atualizar.
 * 
 * @package Application\Entity
 * @author João Víctor Guedes Carrijo <jvgcarrijo@gmail.com>
 * @version 1.0
 * @since 2024-04-06
 * @see Category
 * 
 * @ORM\Entity
 * @ORM\Table(name="products")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price = '0.00';

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {        
        $description = $description !== null ? trim($description) : null;
        $this->description = $description !== '' ? $description : null;
    
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }
    public function setPrice(string $price): self
    {
        $this->price = number_format((float) $price, 2, '.', '');

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }
    public function setStock(int $stock): self
    {
        $this->stock = max(0, $stock);

        return $this;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}