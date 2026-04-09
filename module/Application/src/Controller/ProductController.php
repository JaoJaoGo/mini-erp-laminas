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
use RuntimeException;

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

        $postData = $request->getPost()->toArray();
        $fileData = $request->getFiles()->toArray();

        $postData['categories'] = $this->productService->normalizeCategoryIds($postData['categories'] ?? []);
        $formData = array_merge($postData, $fileData);

        $form->setData($formData);

        if (!$form->isValid()) {
            $this->productService->appendCategoryValidationError($form, $postData['categories']);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: null,
            );
        }

        /** @var array{name:string, description:mixed, price:mixed, stock:mixed, isActive:mixed} $validatedData */
        $validatedData = $form->getData();

        try {
            $this->productService->create($validatedData, $postData['categories'], $request);
        } catch (RuntimeException $exception) {
            $form->get('image')->setMessages([
                'uploadError' => $exception->getMessage(),
            ]);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: null,
            );
        }

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

        $postData = $request->getPost()->toArray();
        $fileData = $request->getFiles()->toArray();

        $postData['categories'] = $this->productService->normalizeCategoryIds($postData['categories'] ?? []);
        $formData = array_merge($postData, $fileData);

        $form->setData($formData);

        if (!$form->isValid()) {
            $this->productService->appendCategoryValidationError($form, $postData['categories']);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: $product,
            );
        }

        /** @var array{name:string, description:mixed, price:mixed, stock:mixed, isActive:mixed} $validatedData */
        $validatedData = $form->getData();

        try {
            $this->productService->update($product, $validatedData, $postData['categories'], $request);
        } catch (RuntimeException $exception) {
            $form->get('image')->setMessages([
                'uploadError' => $exception->getMessage(),
            ]);

            return $this->productResponse->form(
                user: $this->authService->getAuthenticatedUser(),
                form: $form,
                categories: $categories,
                product: $product,
            );
        }

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
