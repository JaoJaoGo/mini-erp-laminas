<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;

/**
 * Serviço responsável pela geração de métricas e dados para o dashboard.
 *
 * O MetricService consulta os repositórios de categoria e produto para construir
 * totais gerais e séries de dados para gráficos de status e por categoria.
 *
 * Métodos disponíveis:
 * - getDashboardData(): array
 * - getProductsPerCategoryChart(): array
 * - getProductsStatusChart(): array
 */
class MetricService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly EntityManager $entityManager,
    ) { }

    public function getDashboardData(): array
    {
        $totalCategories = (int) $this->entityManager->getRepository(Category::class)
            ->count([
                'deletedAt' => null,
            ]);
        $totalProducts = (int) $this->entityManager->getRepository(Product::class)
            ->count([
                'deletedAt' => null,
            ]);

        return [
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
        ];
    }

    /**
     * @return array{
     *      labels: array<int, string>,
     *      values: array<int, int>
     * }
     */
    public function getProductsPerCategoryChart(): array
    {
        $rows = $this->categoryRepository->getProductCountGroupedByCategory();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            if ($row['total'] <= 0) {
                continue;
            }

            $labels[] = $row['name'];
            $values[] = $row['total'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{
     *      labels: array<int, string>,
     *      values: array<int, int>
     * }
     */
    public function getProductsStatusChart(): array
    {
        $status = $this->productRepository->getActiveVsInactiveCount();

        return [
            'labels' => [
                'Ativos',
                'Inativos',
            ],
            'values' => [
                $status['active'],
                $status['inactive'],
            ],
        ];
    }
}