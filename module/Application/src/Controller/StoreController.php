<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Response\StoreResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Application\Service\ProductService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Controlador responsável pela loja pública.
 *
 * Fornece listagem pública de produtos, filtros por nome/categoria e visualização de produto,
 * sem exigir autenticação.
 *
 * Ações disponíveis:
 * - indexAction: retorna a listagem pública de produtos com filtros e paginação.
 * - viewAction: exibe detalhes de um produto na loja pública.
 */
class StoreController extends AbstractActionController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly StoreResponse $storeResponse,
        private readonly AuthService $authService,
    ) { }

    public function indexAction(): ViewModel
    {
        $name = trim((string) $this->params()->fromQuery('name', ''));
        $categoryId = $this->normalizeCategoryId(
            $this->params()->fromQuery('category', '')
        );
        $sort = $this->normalizeSort(
            $this->params()->fromQuery('sort', 'latest')
        );
        $inStock = $this->normalizeBoolean(
            $this->params()->fromQuery('inStock', '')
        );
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $perPage = 12;

        $result = $this->productService->getStoreProductsPaginated(
            $name,
            $categoryId,
            $sort,
            $inStock,
            $page,
            $perPage
        );

        $categories = $this->categoryService->getStoreCategoriesWithProductCount();

        return $this->storeResponse->index(
            user: $this->authService->getAuthenticatedUser(),
            products: $result['items'],
            categories: $categories,
            filters: $this->storeResponse->createFilters($name, $categoryId, $sort, $inStock),
            pagination: $this->storeResponse->createPagination(
                total: $result['total'],
                page: $result['page'],
                perPage: $result['perPage'],
                totalPages: $result['totalPages'],
            ),
        );
    }

    public function viewAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $product = $this->productService->findStoreById($id);

        if ($product === null) {
            return $this->notFoundAction();
        }

        $relatedProducts = $this->productService->getRelatedStoreProducts($product, 4);

        return $this->storeResponse->view(
            user: $this->authService->getAuthenticatedUser(),
            product: $product,
            relatedProducts: $relatedProducts,
        );
    }

    private function normalizeCategoryId(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $categoryId = (int) $value;

        return $categoryId > 0 ? $categoryId : null;
    }

    private function normalizeSort(mixed $value): string
    {
        $value = trim((string) $value);

        $allowed = [
            'latest',
            'price_asc',
            'price_desc',
            'name_asc',
            'name_desc',
        ];

        return in_array($value, $allowed, true) ? $value : 'latest';
    }

    private function normalizeBoolean(mixed $value): bool
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }
}