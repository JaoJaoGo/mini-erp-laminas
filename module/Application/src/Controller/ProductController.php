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
        $name = trim((string) $this->params()->fromQuery('name', '')).
        $categoryFilter = trim((string) $this->params()->fromQuery('category', ''));

        $repository = $this->entityManager->getRepository(Product::class);
        $qb = $repository->createQueryBuilder('p')->leftJoin('p.category', 'c')->addSelect('c')->orderBy('p.id', 'DESC');

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
        $categories = $this->getCategoriesForForm();

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
                    'category' => '',
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
                    'category' => (string) ($data['category'] ?? ''),
                ],
            ]))->setTemplate('application/product/form');
        }

        $category = $this->findCategoryFromData($data);

        $product = new Product();
        $product->setName((string) $data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $data['price']));
        $product->setStock((int) ($data['stock'] ?? 0));
        $product->setIsActive(((string) ($data['isActive'] ?? '1')) === '1');
        $product->setCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->redirect()->toRoute('product');
    }

    public function editAction(): ViewModel|Response
    {
        $id = $this->params()->fromRoute('id', 0);

        /** @var Product|null $product */
        $product = $this->entityManager->find(Product::class, $id);

        if (!$product instanceof Product) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $categories = $this->getCategoriesForForm();

        if (!$request instanceof Request || !$request->isPost()) {
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
                    'category' => $product->getCategory()?->getId() !== null
                        ? (string) $product->getCategory()?->getId()
                        : '',
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
                    'category' => (string) ($data['category'] ?? ''),
                ],
            ]))->setTemplate('application/product/form');
        }

        $category = $this->findCategoryFromData($data);

        $product->setName((string) $data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $data['price']));
        $product->setStock((int) ($data['stock'] ?? 0));
        $product->setIsActive(((string) ($data['isActive'] ?? '1')) === '1');
        $product->setCategory($category);

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
        $categoryId = trim((string) ($data['category'] ?? ''));

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

        if ($categoryId !== '') {
            $category = $this->entityManager->find(Category::class, (int) $categoryId);

            if (! $category instanceof Category) {
                $errors[] = 'A categoria selecionada é inválida.';
            }
        }

        return $errors;
    }

    /**
     * @return array<int, Category>
     */
    private function getCategoriesForForm(): array
    {
        return $this->entityManager
            ->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function findCategoryFromData(array $data): ?Category
    {
        $categoryId = (int) ($data['category'] ?? 0);

        if ($categoryId <= 0) {
            return null;
        }

        $category = $this->entityManager->find(Category::class, $categoryId);

        return $category instanceof Category ? $category : null;
    }

    private function normalizeMoneyValue(string $value): string
    {
        $value = trim($value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return $value;
    }
}
