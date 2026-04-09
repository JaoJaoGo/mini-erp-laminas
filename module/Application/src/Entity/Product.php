<?php

declare(strict_types=1);

namespace Application\Entity;

use Application\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade que representa um produto.
 * Cada produto pode pertencer a várias categorias, e cada categoria pode conter vários produtos.
 *
 * @package Application\Entity
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
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

    #[ORM\Column(name: 'image_path', type: 'string', length: 255, nullable: true)]
    private ?string $imagePath = null;

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

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinTable(name: 'product_categories')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }
    public function setImagePath(?string $imagePath): self
    {
        $imagePath = $imagePath !== null ? trim($imagePath) : null;
        $this->imagePath = $imagePath !== '' ? $imagePath : null;

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
    public function getIsActive(): bool
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
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
    public function softDelete(): self
    {
        $this->deletedAt = new \DateTimeImmutable();

        return $this;
    }
    public function restore(): self
    {
        $this->deletedAt = null;

        return $this;
    }
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }
    /**
     * Compatibilidade com código legado.
     *
     * @return Collection<int, Category>
     */
    public function getCategory(): Collection
    {
        return $this->getCategories();
    }
    /**
     * @param Collection<int, Category> $categories
     */
    public function setCategories(Collection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }
    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }
    public function clearCategories(): self
    {
        $this->categories->clear();

        return $this;
    }
}