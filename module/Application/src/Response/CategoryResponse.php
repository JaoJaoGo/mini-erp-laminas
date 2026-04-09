<?php

declare(strict_types=1);

namespace Application\Response;

use Application\Entity\Category;
use Application\Entity\User;
use Application\Form\CategoryForm;
use Laminas\View\Model\ViewModel;

class CategoryResponse
{
    /**
     * @param list<Category> $category
     * @param array{
     *     total:int,
     *     page:int,
     *     perPage:int,
     *     totalPages:int
     * } $pagination
     */
    public function index(?User $user, array $categories, array $filters, array $pagination): ViewModel
    {
        return new ViewModel([
            'user' => $user,
            'categories' => $categories,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    public function form(?User $user, CategoryForm $form, ?Category $category = null): ViewModel
    {
        return (new ViewModel([
            'user' => $user,
            'category' => $category,
            'form' => $form,
        ]))->setTemplate('application/category/form');
    }

    public function createFormData(?Category $category = null): array
    {
        return [
            'name' => $category?->getName() ?? '',
            'description' => $category?->getDescription() ?? '',
        ];
    }

    public function createFilters(string $name = ''): array
    {
        return [
            'name' => $name,
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