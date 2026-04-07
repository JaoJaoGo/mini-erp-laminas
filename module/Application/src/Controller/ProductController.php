<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Service\AuthService;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ProductController extends AbstractActionController
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly AuthService $authService,
    ) { }

    public function indexAction(): viewModel
    {
        $name = trim((string) $this->params()->fromQuery('name', ''));
        $categoryFilter = trim((string) $this->params()->fromQuery('category', ''));

        $repository = $this->entityManager->getRepository(Product::class);

        $qb = $repository
            ->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.id', 'DESC')
            ->distinct();
        
        if ($name !== '') {
            $qb->andWhere('p.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        if ($categoryFilter !== '') {
            $qb->andWhere('c.name LIKE :category')->setParameter('category', '%' . $categoryFilter . '%');
        }

        $products = $qb->getQuery()->getResult();

        return new ViewModel([
            'user' => $this->authService->getAuthenticatedUser(),
            'products' => $products,
            'filters' => [
                'name' => $name,
                'category' => $categoryFilter,
            ],
        ]);
    }

    public function createAction(): viewModel|Response
    {
        $request = $this->getRequest();
        $categories = $this->getCategoryForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => null,
                'categories' => $categories,
                'errors' => [],
                'formData' => [
                    'name' => '',
                    'description' => '',
                    'price' => '',
                    'stock' => '0',
                    'isActive' => '1',
                    'categories' => [],
                ],
            ]))->setTemplate('application/product/form');
        }

        $data = $request->getPost()->toArray();
        $errors = $this->validateProductData($data);

        if ($errors !== []) {
            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => null,
                'categories' => $categories,
                'errors' => $errors,
                'formData' => [
                    'name' => (string) ($data['name'] ?? ''),
                    'description' => (string) ($data['description'] ?? ''),
                    'price' => (string) ($data['price'] ?? ''),
                    'stock' => (string) ($data['stock' ?? '0']),
                    'isActive' => (string) ($data['isActive'] ?? '1'),
                    'categories' => $this->normalizeCategoryIds($data['categories'] ?? []),
                ],
            ]))->setTemplate('application/product/form');
        }

        $product = new Product();
        $product->setName((string) $data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $data['price']));
        $product->setStock((int) ($data['stock'] ?? 0));
        $product->setIsActive(((string) ($data['isActive'] ?? '1')) === '1');

        foreach ($this->findCategoriesFromData($data) as $category) {
            $product->addCategory($category);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->redirect()->toRoute('product');
    }

    // TODO: Adicionar funcionalidade de remover categorias do produto (e não apenas adicionar mais)
    public function editAction(): ViewModel|Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        /** @var Product|null $product */
        $product = $this->entityManager->find(Product::class, $id);

        if (!$product instanceof Product) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $categories = $this->getCategoryForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            $selectedCategories = array_map(
                static fn (Category $category): string => (string) $category->getId(),
                $product->getCategory()->toArray()
            );

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => $product,
                'categories' => $categories,
                'errors' => [],
                'formData' => [
                    'name' => $product->getName(),
                    'description' => $product->getDescription() ?? '',
                    'price' => (string) $product->getPrice(),
                    'stock' => (string) $product->getStock(),
                    'isActive' => $product->isActive() ? '1' : '0',
                    'categories' => $selectedCategories,
                ],
            ]))->setTemplate('application/product/form');
        }

        $data = $request->getPost()->toArray();
        $errors = $this->validateProductData($data);

        if ($errors !== []) {
            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => $product,
                'categories' => $categories,
                'errors' => $errors,
                'formData' => [
                    'name' => (string) ($data['name'] ?? ''),
                    'description' => (string) ($data['description'] ?? ''),
                    'price' => (string) ($data['price'] ?? ''),
                    'stock' => (string) ($data['stock'] ?? '0'),
                    'isActive' => (string) ($data['isActive'] ?? '1'),
                    'categories' => $this->normalizeCategoryIds($data['categories'] ?? []),
                ],
            ]))->setTemplate('application/product/form');
        }

        $product->setName((string) $data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $data['price']));
        $product->setStock((int) ($data['stock'] ?? 0));
        $product->setIsActive(((string) ($data['isActive'] ?? '1')) === '1');

        foreach($this->findCategoriesFromData($data) as $category) {
            $product->addCategory($category);
        }

        $this->entityManager->flush();

        return $this->redirect()->toRoute('product');
    }

    public function deleteAction(): Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        /** @var Product|null $product */
        $product = $this->entityManager->find(Product::class, $id);

        if ($product instanceof Product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }

        return $this->redirect()->toRoute('product');
    }

    private function validateProductData(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        $price = trim((string) ($data['price'] ?? ''));
        $stock = trim((string) ($data['stock'] ?? '0'));
        $categoryIds = $this->normalizeCategoryIds($data['categories'] ?? []);

        if ($name === '') {
            $errors[] = 'O nome do produto é obrigatório.';
        }

        if (mb_strlen($name) > 150) {
            $errors[] = 'O nome do produto deve ter no máximo 150 caracteres.';
        }

        if ($price === '') {
            $errors[] = 'O preço do produto é obrigatório.';
        } elseif (! is_numeric($this->normalizeMoneyValue($price))) {
            $errors[] = 'Informe um preço válido.';
        }

        if ($stock === '') {
            $errors[] = 'O estoque é obrigatório.';
        } elseif (filter_var($stock, FILTER_VALIDATE_INT) === false) {
            $errors[] = 'O estoque deve ser um número inteiro.';
        } elseif ((int) $stock < 0) {
            $errors[] = 'O estoque não pode ser negativo.';
        }

        foreach ($categoryIds as $categoryId) {
            $category = $this->entityManager->find(Category::class, (int) $categoryId);

            if (!$category instanceof Category) {
                $errors[] = "Uma das categorias selecionadas é inválida.";
                break;
            }
        }

        return $errors;
    }

    /**
     * @return array<int, Category>
     */
    private function getCategoryForForm(): array
    {
        return $this->entityManager
            ->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeCategoryIds(mixed $categoryIds): array
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
     * @return array<int, Category>
     */
    private function findCategoriesFromData(array $data): array
    {
        $categories = [];

        foreach ($this->normalizeCategoryIds($data['categories'] ?? []) as $categoryId) {
            $category = $this->entityManager->find(Category::class, (int) $categoryId);

            if ($category instanceof Category) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    private function normalizeMoneyValue(string $value): string
    {
        $value = trim($value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return $value;
    }
}
