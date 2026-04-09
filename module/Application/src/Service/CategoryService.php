<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Category;
use Application\Repository\CategoryRepository;
use Doctrine\ORM\EntityManager;

class CategoryService
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly CategoryRepository $categoryRepository,
    ) { }

    /**
     * @return array{
     *     items: list<Category>,
     *     total: int,
     *     page: int,
     *     perPage: int,
     *     totalPages: int
     * }
     */
    public function getFilteredCategoriesPaginated(
        string $name = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        return $this->categoryRepository->findFilteredPaginated($name, $page, $perPage);
    }

    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->findActiveById($id);
    }

    public function createEmpty(): Category
    {
        return new Category();
    }

    public function create(array $data): Category
    {
        $category = $this->createEmpty();
        
        $this->fillEntity($category, $data);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $this->fillEntity($category, $data);
        $this->entityManager->flush();

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->isDeleted()) {
            return;
        }

        $category->softDelete();
        $this->entityManager->flush();
    }

    public function fillEntity(Category $category, array $data): Category
    {
        $category->setName((string) ($data['name'] ?? ''));
        $category->setDescription($data['description'] ?? null);

        return $category;
    }
}