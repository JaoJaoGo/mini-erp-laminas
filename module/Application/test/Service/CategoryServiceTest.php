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

    public function testGetFilteredCategoriesPaginatedReturnsArrayWithPaginationData(): void
    {
        $category1 = new Category();
        $category1->setName('Eletrônicos');

        $this->categoryRepository->expects(self::once())
            ->method('findFilteredPaginated')
            ->with('Eletrônicos', 1, 10)
            ->willReturn([
                'items' => [$category1],
                'total' => 1,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 1,
            ]);

        $result = $this->service->getFilteredCategoriesPaginated('Eletrônicos', 1, 10);

        self::assertIsArray($result);
        self::assertArrayHasKey('items', $result);
        self::assertSame([$category1], $result['items']);
        self::assertSame(1, $result['total']);
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
            ->method('findActiveById')
            ->with(5)
            ->willReturn($category);

        self::assertSame($category, $this->service->findById(5));
    }

    public function testDeleteRemovesCategoryAndFlushes(): void
    {
        $category = $this->createMock(Category::class);
        $category->expects(self::once())
            ->method('isDeleted')
            ->willReturn(false);

        $category->expects(self::once())
            ->method('softDelete')
            ->willReturn($category);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->service->delete($category);
    }
}
