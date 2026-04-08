<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Form\ProductForm;
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
        private readonly ProductForm $productForm,
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
        $form = clone $this->productForm;
        $categories = $this->getCategoryForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            $form->setData([
                'name' => '',
                'description' => '',
                'price' => '',
                'stock' => '0',
                'isActive' => '1',
                'categories' => [],
            ]);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => null,
                'categories' => $categories,
                'form' => $form,
            ]))->setTemplate('application/product/form');
        }

        $data = $request->getPost()->toArray();
        $data['categories'] = $this->normalizeCategoryIds($data['categories'] ?? []);
        $form->setData($data);

        if (!$form->isValid()) {
            $this->appendCategoryValidationError($form, $data['categories']);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => null,
                'categories' => $categories,
                'form' => $form,
            ]))->setTemplate('application/product/form');
        }

        $product = new Product();
        $validatedData = $form->getData();

        $product->setName((string) $validatedData['name']);
        $product->setDescription($validatedData['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $validatedData['price']));
        $product->setStock((int) ($validatedData['stock'] ?? 0));
        $product->setIsActive(((string) ($validatedData['isActive'] ?? '1')) === '1');

        foreach ($this->findCategoriesByIds($data['categories']) as $category) {
            $product->addCategory($category);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->redirect()->toRoute('product');
    }

    public function editAction(): ViewModel|Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        /** @var Product|null $product */
        $product = $this->entityManager->find(Product::class, $id);

        if (!$product instanceof Product) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $form = clone $this->productForm;
        $categories = $this->getCategoryForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            $form->setData([
                'name' => $product->getName(),
                'description' => $product->getDescription() ?? '',
                'price' => str_replace('.', ',', (string) $product->getPrice()),
                'stock' => (string) $product->getStock(),
                'isActive' => $product->isActive() ? '1' : '0',
                'categories' => array_map(
                    static fn (Category $category): string => (string) $category->getId(),
                    $product->getCategory()->toArray()
                ),
            ]);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => $product,
                'categories' => $categories,
                'form' => $form,
            ]))->setTemplate('application/product/form');
        }

        $data = $request->getPost()->toArray();
        $data['categories'] = $this->normalizeCategoryIds($data['categories'] ?? []);
        $form->setData($data);

        if (!$form->isValid()) {
            $this->appendCategoryValidationError($form, $data['categories']);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'product' => $product,
                'categories' => $categories,
                'form' => $form,
            ]))->setTemplate('application/product/form');
        }

        $validatedData = $form->getData();

        $product->setName((string) $validatedData['name']);
        $product->setDescription($validatedData['description'] ?? null);
        $product->setPrice($this->normalizeMoneyValue((string) $validatedData['price']));
        $product->setStock((int) ($validatedData['stock'] ?? 0));
        $product->setIsActive(((string) ($validatedData['isActive'] ?? '1')) === '1');

        $product->clearCategories();

        foreach($this->findCategoriesByIds($data['categories']) as $category) {
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
     * @param array<int, string> $categoryIds
     * @return array<int, Category>
     */
    private function findCategoriesByIds(array $categoryIds): array
    {
        $categories = [];

        foreach ($categoryIds as $categoryId) {
            $category = $this->entityManager->find(Category::class, (int) $categoryId);

            if ($category instanceof Category) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * @param array<int, string> $categoryIds
     */
    private function appendCategoryValidationError(ProductForm $form, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            $category = $this->entityManager->find(Category::class, (int) $categoryId);

            if (!$category instanceof Category) {
                $form->get('categories')->setMessages([
                    'invalidCategory' => 'Uma das categorias selecionadas é inválida.',
                ]);
                break;
            }
        }
    }

    private function normalizeMoneyValue(string $value): string
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
}
