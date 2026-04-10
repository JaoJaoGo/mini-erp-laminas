<?php

declare(strict_types=1);

namespace Application\Response;

use Application\Entity\Product;
use Application\Entity\User;
use Laminas\View\Model\ViewModel;

/**
 * Classe responsável pela montagem das respostas para as páginas da loja pública.
 *
 * O StoreResponse monta ViewModels para a página de listagem de produtos da loja,
 * filtros e paginação, bem como para a visualização de detalhes de produto.
 */
class StoreResponse
{
    /**
     * @param list<Product> $products
     * @param list<array{id:int,name:string,total:int}> $categories
     * @param array{name:string, categoryId:?int, sort:string, inStock: bool} $filters
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

    public function view(?User $user, Product $product, array $relatedProducts): ViewModel
    {
        return (new ViewModel([
            'user' => $user,
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]))->setTemplate('application/store/view');
    }

    /**
     * @return array{name:string, categoryId:?int, sort: string, inStock: bool}
     */
    public function createFilters(
        string $name = '',
        ?int $categoryId = null,
        string $sort = 'latest',
        bool $inStock = false
        ): array {
        return [
            'name' => $name,
            'categoryId' => $categoryId,
            'sort' => $sort,
            'inStock' => $inStock,
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