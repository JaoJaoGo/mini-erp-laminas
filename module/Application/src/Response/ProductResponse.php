<?php

declare(strict_types=1);

namespace Application\Response;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Entity\User;
use Application\Form\ProductForm;
use Laminas\View\Model\ViewModel;

class ProductResponse
{
    /**
     * @param list<Product> $products
     * @param array{name:string, category:string} $filters
     * @param array{
     *     total:int,
     *     page:int,
     *     perPage:int,
     *     totalPages:int
     * } $pagination
     */
    public function index(?User $user, array $products, array $filters, array $pagination): ViewModel
    {
        return new ViewModel([
            'user' => $user,
            'products' => $products,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    /**
     * @param list<Category> $categories
     */
    public function form(?User $user, ProductForm $form, array $categories, ?Product $product = null): ViewModel
    {
        return (new ViewModel([
            'user' => $user,
            'product' => $product,
            'categories' => $categories,
            'form' => $form,
        ]))->setTemplate('application/product/form');
    }

    /**
     * @return array{name:string, description:string, price:string, stock:string, isActive:string, categories:array<int,string>}
     */
    public function createFormData(?Product $product = null): array
    {
        if ($product === null) {
            return [
                'name' => '',
                'description' => '',
                'imagePath' => '',
                'price' => '',
                'stock' => '0',
                'isActive' => '1',
                'categories' => [],
            ];
        }

        return [
            'name' => $product->getName(),
            'description' => $product->getDescription() ?? '',
            'imagePath' => $product->getImagePath() ?? '',
            'price' => str_replace('.', ',', (string) $product->getPrice()),
            'stock' => (string) $product->getStock(),
            'isActive' => $product->isActive() ? '1' : '0',
            'categories' => array_map(
                static fn (Category $category): string => (string) $category->getId(),
                $product->getCategory()->toArray()
            ),
        ];
    }

    /**
     * @return array{name:string, category:string}
     */
    public function createFilters(string $name = '', string $category = ''): array
    {
        return [
            'name' => $name,
            'category' => $category,
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