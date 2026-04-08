<?php

declare(strict_types=1);

namespace Application\Response;

use Application\Entity\Category;
use Application\Form\CategoryForm;
use Application\Entity\User;
use Laminas\View\Model\ViewModel;

class CategoryResponse
{
    public function index(?User $user, array $categories, array $filters): ViewModel
    {
        return new ViewModel([
            'user' => $user,
            'categories' => $categories,
            'filters' => $filters,
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
}