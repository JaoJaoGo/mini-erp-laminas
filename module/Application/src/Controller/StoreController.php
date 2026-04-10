<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Response\StoreResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Application\Service\ProductService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        $page = max(1, (int) $this->params()->fromQuery('page', 1));
        $perPage = 12;

        $result = $this->productService->getStoreProductsPaginated(
            $name,
            $categoryId,
            $page,
            $perPage
        );

        $categories = $this->categoryService->getStoreCategoriesWithProductCount();

        return $this->storeResponse->index(
            user: $this->authService->getAuthenticatedUser(),
            products: $result['items'],
            categories: $categories,
            filters: $this->storeResponse->createFilters($name, $categoryId),
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

        return $this->storeResponse->view(
            user: $this->authService->getAuthenticatedUser(),
            product: $product,
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
}