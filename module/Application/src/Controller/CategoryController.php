<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Category;
use Application\Form\CategoryForm;
use Application\Response\CategoryResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CategoryController extends AbstractActionController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryResponse $categoryResponse,
        private readonly AuthService $authService,
        private readonly CategoryForm $categoryForm,
    ) { }

    public function indexAction(): ViewModel
    {
        $name = trim((string) $this->params()->fromQuery('name', ''));
        $page = max(1, (int) $this->params()->fromQuery('page', ''));
        $perPage = 10;

        $result = $this->categoryService->getFilteredCategoriesPaginated(
            $name,
            $page,
            $perPage
        );

        return $this->categoryResponse->index(
            user: $this->authService->getAuthenticatedUser(),
            categories: $result['items'],
            filters: $this->categoryResponse->createFilters($name),
            pagination: $this->categoryResponse->createPagination(
                total: $result['total'],
                page: $result['page'],
                perPage: $result['perPage'],
                totalPages: $result['totalPages'],
            ),
        );
    }

    public function createAction(): ViewModel|Response
    {
        $request = $this->getRequest();
        $form = clone $this->categoryForm;

        if (!$this->isPostRequest($request)) {
            $form->setData($this->categoryResponse->createFormData());

            return $this->renderForm($form);
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if (!$form->isValid()) {
            $category = $this->categoryService->fillEntity(
                $this->categoryService->createEmpty(),
                $data
            );

            return $this->renderForm($form, $category);
        }

        /** @var array{name:string, description:mixed} $validatedData */
        $validatedData = $form->getData();

        $this->categoryService->create($validatedData);

        return $this->redirect()->toRoute('category');
    }

    public function editAction(): ViewModel|Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $category = $this->categoryService->findById($id);

        if ($category === null) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $form = clone $this->categoryForm;

        if (!$this->isPostRequest($request)) {
            $form->setData($this->categoryResponse->createFormData($category));

            return $this->renderForm($form, $category);
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if (!$form->isValid()) {
            $this->categoryService->fillEntity($category, $data);

            return $this->renderForm($form, $category);
        }

        /** @var array{name:string, description:mixed} $validatedData */
        $validatedData = $form->getData();

        $this->categoryService->update($category, $validatedData);

        return $this->redirect()->toRoute('category');
    }

    public function deleteAction(): Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $category = $this->categoryService->findById($id);

        if ($category !== null) {
            $this->categoryService->delete($category);
        }

        return $this->redirect()->toRoute('category');
    }

    private function isPostRequest(mixed $request): bool
    {
        return $request instanceof Request && $request->isPost();
    }

    private function renderForm(CategoryForm $form, ?Category $category = null): ViewModel
    {
        return $this->categoryResponse->form(
            user: $this->authService->getAuthenticatedUser(),
            form: $form,
            category: $category,
        );
    }
}