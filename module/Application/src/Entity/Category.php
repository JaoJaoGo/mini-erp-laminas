<?php

declare(strict_types=1);

namespace Application\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa uma categoria de produtos.
 * Cada categoria pode conter vários produtos, e cada produto pertence a uma única categoria.
 * 
 * Propriedades:
 * - id: Identificador único da categoria (inteiro, auto-incrementado).
 * - name: Nome da categoria (string, máximo de 150 caracteres).
 * - description: Descrição da categoria (string, opcional).
 * - createdAt: Data e hora de criação da categoria (DateTimeImmutable).
 * - updatedAt: Data e hora da última atualização da categoria (DateTimeImmutable).
 * - products: Coleção de produtos associados a esta categoria (OneToMany).
 * 
 * Métodos:
 * - getId(): Retorna o ID da categoria.
 * - getName(): Retorna o nome da categoria.
 * - setName(string $name): Define o nome da categoria.
 * - getDescription(): Retorna a descrição da categoria.
 * - setDescription(?string $description): Define a descrição da categoria.
 * - getCreatedAt(): Retorna a data de criação da categoria.
 * - getUpdatedAt(): Retorna a data da última atualização da categoria.
 * - getProducts(): Retorna a coleção de produtos associados a esta categoria.
 * - addProduct(Product $product): Adiciona um produto à categoria.
 * - removeProduct(Product $product): Remove um produto da categoria.
 * 
 * Observações:
 * - A classe utiliza anotações do Doctrine ORM para mapear a entidade e suas propriedades para o banco de dados.
 * - Os métodos onPrePersist e onPreUpdate são usados para definir automaticamente as datas de criação e atualização da categoria.
 * - A relação entre Category e Product é bidirecional, permitindo acesso fácil aos produtos de uma categoria e à categoria de um produto.
 * - A classe é projetada para ser usada em um contexto de aplicação que gerencia produtos e categorias, como um sistema de e-commerce.
 * - A classe é parte do namespace Application\Entity, indicando que é uma entidade de domínio dentro da aplicação.
 * - A classe é marcada com #[ORM\HasLifecycleCallbacks] para permitir a execução de métodos específicos durante o ciclo de vida da entidade, como antes de persistir ou atualizar.
 * 
 * @package Application\Entity
 * @author João Víctor Guedes Carrijo <jvgcarrijo@gmail.com>
 * @version 1.0
 * @since 2024-04-06
 * @see Product
 * 
 * @ORM\Entity
 * @ORM\Table(name="categories")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\HasLifecycleCallbacks]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

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
        $this->name = $name;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }

        return $this;
    }
}