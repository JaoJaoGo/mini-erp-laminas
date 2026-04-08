<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Product;
use Application\Form\ProductForm;
use Application\Response\ProductResponse;
use Application\Service\AuthService;
use Application\Service\ProductService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ProductController extends AbstractActionController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductResponse $productResponse,
        private readonly AuthService $authService,
        private readonly ProductForm $productForm,
    ) { }

    public function indexAction(): viewModel
    {
        $name = trim((string) $this->params()->fromQuery('name', ''));
        $categoryFilter = trim((string) $this->params()->fromQuery('category', ''));

        return $this->productResponse->index(
            user: $this->authService->getAuthenticatedUser(),
            products: $this->productService->getFilteredProducts($name, $categoryFilter),
            filters: $this->productResponse->createFilters($name, $categoryFilter),
        );
    }

    public function createAction(): viewModel|Response
    {
        $request = $this->getRequest();
        $form = clone $this->productForm;
        $categories = $this->productService->getCategoriesForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            $form->setData($this->productResponse->createFormData());

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: null,
            );
        }

        $data = $request->getPost()->toArray();
        $data['categories'] = $this->productService->normalizeCategoryIds($data['categories'] ?? []);
        $form->setData($data);

        if (!$form->isValid()) {
            $this->productService->appendCategoryValidationError($form, $data['categories']);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: null,
            );
        }

        /** @var array{name:string, description:mixed, price:mixed, stock:mixed, isActive:mixed} $validatedData */
        $validatedData = $form->getData();

        $this->productService->create($validatedData, $data['categories']);

        return $this->redirect()->toRoute('product');
    }

    public function editAction(): ViewModel|Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $product = $this->productService->findById($id);

        if (!$product instanceof Product) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $form = clone $this->productForm;
        $categories = $this->productService->getCategoriesForForm();

        if (!$request instanceof Request || !$request->isPost()) {
            $form->setData($this->productResponse->createFormData($product));

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: $product,
            );
        }

        $data = $request->getPost()->toArray();
        $data['categories'] = $this->productService->normalizeCategoryIds($data['categories'] ?? []);
        $form->setData($data);

        if (!$form->isValid()) {
            $this->productService->appendCategoryValidationError($form, $data['categories']);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: $product,
            );
        }

        /** @var array{name:string, description:mixed, price:mixed, stock:mixed, isActive:mixed} $validatedData */
        $validatedData = $form->getData();

        $this->productService->update($product, $validatedData, $data['categories']);

        return $this->redirect()->toRoute('product');
    }

    public function deleteAction(): Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $product = $this->productService->findById($id);

        if ($product instanceof Product) {
            $this->productService->delete($product);
        }

        return $this->redirect()->toRoute('product');
    }
}
