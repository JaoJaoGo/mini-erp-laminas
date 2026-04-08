<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Category;
use Application\Repository\CategoryRepository;
use Application\Service\CategoryService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    private CategoryService $service;
    private EntityManager $entityManager;
    private CategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->service = new CategoryService($this->entityManager, $this->categoryRepository);
    }

    public function testFillEntitySetsNameAndDescription(): void
    {
        $category = new Category();
        $result = $this->service->fillEntity($category, [
            'name' => 'Eletrônicos',
            'description' => 'Categoria de produtos eletrônicos',
        ]);

        self::assertSame($category, $result);
        self::assertSame('Eletrônicos', $result->getName());
        self::assertSame('Categoria de produtos eletrônicos', $result->getDescription());
    }

    public function testCreatePersistsCategoryAndFlushes(): void
    {
        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Category::class));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $category = $this->service->create([
            'name' => 'Escritório',
            'description' => 'Móveis e material de escritório',
        ]);

        self::assertInstanceOf(Category::class, $category);
        self::assertSame('Escritório', $category->getName());
        self::assertSame('Móveis e material de escritório', $category->getDescription());
    }

    public function testFindByIdReturnsCategoryWhenFound(): void
    {
        $category = new Category();

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with(5)
            ->willReturn($category);

        self::assertSame($category, $this->service->findById(5));
    }

    public function testDeleteRemovesCategoryAndFlushes(): void
    {
        $category = new Category();

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with($category);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->service->delete($category);
    }
}
