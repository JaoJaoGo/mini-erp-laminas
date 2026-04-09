<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Form\ProductForm;
use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Laminas\Http\PhpEnvironment\Request;
use Application\Service\ProductImageService;

class ProductService
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductImageService $productImageService,
    ) { }

    /**
     * @return list<Product>
     */
    public function getFilteredProducts(string $name = '', string $category = ''): array
    {
        return $this->productRepository->findFiltered($name, $category);
    }

    public function findById(int $id): ?Product
    {
        $product = $this->productRepository->find($id);

        return $product instanceof Product ? $product : null;
    }

    public function createEmpty(): Product
    {
        return new Product();
    }

    /**
     * @return list<Category>
     */
    public function getCategoriesForForm(): array
    {
        /** @var list<Category> $categories */
        $categories = $this->categoryRepository->createQueryBuilder('c')->orderBy('c.name', 'ASC')->getQuery()->getResult();

        return $categories;
    }

    /**
     * @return array<int, string>
     */
    public function normalizeCategoryIds(mixed $categoryIds): array
    {
        if (!is_array($categoryIds)) {
            return [];
        }

        $normalized = [];

        foreach ($categoryIds as $categoryId) {
            $categoryId = trim((string) $categoryId);

            if ($categoryId === '' || !ctype_digit($categoryId)) {
                continue;
            }

            $normalized[] = $categoryId;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, string> $categoryIds
     * @return list<Category>
     */
    public function findCategoriesByIds(array $categoryIds): array
    {
        $categories = [];

        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->find((int) $categoryId);

            if ($category instanceof Category) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * @param array<int, string> $categoryIds
     */
    public function appendCategoryValidationError(ProductForm $form, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->find((int) $categoryId);

            if (!$category instanceof Category) {
                $form->get('categories')->setMessages([
                    'invalidCategory' => 'Uma das categorias selecionadas é inválida.',
                ]);
                break;
            }
        }
    }

    public function normalizeMoneyValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(' ', '', $value);

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    public function fillEntity(Product $product, array $data): Product
    {
        $product->setName((string) ($data['name'] ?? ''));
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) ($data['price'] ?? '')));
        $product->setStock((int) ($data['stock'] ?? 0));
        $product->setIsActive(((string) ($data['isActive'] ?? '1')) === '1');

        return $product;
    }

    /**
     * @param array<int, string> $categoryIds
     */
    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->clearCategories();

        foreach ($this->findCategoriesByIds($categoryIds) as $category) {
            $product->addCategory($category);
        }
    }

    public function create(array $data, array $categoryIds, Request $request): Product
    {
        $product = $this->createEmpty();

        $this->fillEntity($product, $data);
        $this->syncCategories($product, $categoryIds);

        $imagePath = $this->productImageService->uploadFromRequest($request);

        if ($imagePath !== null) {
            $product->setImagePath($imagePath);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    public function update(Product $product, array $data, array $categoryIds, Request $request): Product
    {
        $oldImagePath = $product->getImagePath();

        $this->fillEntity($product, $data);
        $this->syncCategories($product, $categoryIds);

        $newImagePath = $this->productImageService->uploadFromRequest($request);
        if ($newImagePath !== null && $newImagePath !== $oldImagePath) {
            $product->setImagePath($newImagePath);
            $this->productImageService->delete($oldImagePath);
        }

        $this->entityManager->flush();

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->productImageService->delete($product->getImagePath());

        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}