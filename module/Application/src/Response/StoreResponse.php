<?php

declare(strict_types=1);

namespace Application\Response;

use Application\Entity\Product;
use Application\Entity\User;
use Laminas\View\Model\ViewModel;

class StoreResponse
{
    /**
     * @param list<Product> $products
     * @param list<array{id:int,name:string,total:int}> $categories
     * @param array{name:string, categoryId:?int} $filters
     * @param array{
     *      total: int,
     *      page: int,
     *      perPage: int,
     *      totalPages: int
     * } $pagination
     */
    public function index(
        ?User $user,
        array $products,
        array $categories,
        array $filters,
        array $pagination
    ): ViewModel {
        return (new ViewModel([
            'user' => $user,
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'pagination' => $pagination,
        ]))->setTemplate('application/store/index');
    }

    public function view(?User $user, Product $product): ViewModel
    {
        return (new ViewModel([
            'user' => $user,
            'product' => $product,
        ]))->setTemplate('application/store/view');
    }

    /**
     * @return array{name:string, categoryId:?int}
     */
    public function createFilters(string $name = '', ?int $categoryId = null): array
    {
        return [
            'name' => $name,
            'categoryId' => $categoryId,
        ];
    }

    /**
     * @return array{
     *      total: int,
     *      page: int,
     *      perPage: int,
     *      totalPages: int
     * }
     */
    public function createPagination(
        int $total,
        int $page,
        int $perPage,
        int $totalPages
    ): array {
        return [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }
}