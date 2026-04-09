<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Application\Service\MetricService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class MetricServiceTest extends TestCase
{
    private MetricService $service;
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->service = new MetricService($this->categoryRepository, $this->productRepository, $this->entityManager);
    }

    public function testGetDashboardDataReturnsTotals(): void
    {
        $categoryRepo = $this->createMock(EntityRepository::class);
        $productRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->expects(self::exactly(2))
            ->method('getRepository')
            ->with(self::logicalOr(self::equalTo(Category::class), self::equalTo(Product::class)))
            ->willReturnCallback(static function (string $class) use ($categoryRepo, $productRepo) {
                return $class === Category::class ? $categoryRepo : $productRepo;
            });

        $categoryRepo->expects(self::once())
            ->method('count')
            ->with(['deletedAt' => null])
            ->willReturn(7);

        $productRepo->expects(self::once())
            ->method('count')
            ->with(['deletedAt' => null])
            ->willReturn(12);

        self::assertSame([
            'totalCategories' => 7,
            'totalProducts' => 12,
        ], $this->service->getDashboardData());
    }

    public function testGetProductsPerCategoryChartFiltersEmptyGroups(): void
    {
        $this->categoryRepository->expects(self::once())
            ->method('getProductCountGroupedByCategory')
            ->willReturn([
                ['name' => 'A', 'total' => 3],
                ['name' => 'B', 'total' => 0],
                ['name' => 'C', 'total' => 5],
            ]);

        self::assertSame([
            'labels' => ['A', 'C'],
            'values' => [3, 5],
        ], $this->service->getProductsPerCategoryChart());
    }

    public function testGetProductsStatusChartReturnsActiveAndInactiveCounts(): void
    {
        $this->productRepository->expects(self::once())
            ->method('getActiveVsInactiveCount')
            ->willReturn(['active' => 8, 'inactive' => 2]);

        self::assertSame([
            'labels' => ['Ativos', 'Inativos'],
            'values' => [8, 2],
        ], $this->service->getProductsStatusChart());
    }
}
