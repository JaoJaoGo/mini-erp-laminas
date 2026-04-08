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
     * @return list<Category>
     */
    public function getFilteredCategories(string $name = ''): array
    {
        return $this->categoryRepository->findFiltered($name);
    }

    public function findById(int $id): ?Category
    {
        $category = $this->categoryRepository->find($id);

        return $category instanceof Category ? $category : null;
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
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function fillEntity(Category $category, array $data): Category
    {
        $category->setName((string) ($data['name'] ?? ''));
        $category->setDescription($data['description'] ?? null);

        return $category;
    }
}