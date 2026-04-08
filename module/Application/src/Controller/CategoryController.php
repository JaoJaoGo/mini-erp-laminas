<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Category;
use Application\Service\AuthService;
use Application\Form\CategoryForm;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CategoryController extends AbstractActionController
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly AuthService $authService,
        private readonly CategoryForm $categoryForm,
    ) { }

    public function indexAction(): ViewModel
    {
        $name = trim((string) $this->params()->fromQuery('name', ''));

        $repository = $this->entityManager->getRepository(Category::class);
        $qb = $repository->createQueryBuilder('c')->orderBy('c.id', 'DESC');

        if ($name !== '') {
            $qb->andWhere('c.name LIKE :name')->setParameter('name', '%' . $name . "%");
        }

        $categories = $qb->getQuery()->getResult();

        return new ViewModel([
            'user' => $this->authService->getAuthenticatedUser(),
            'categories' => $categories,
            'filters' => [
                'name' => $name,
            ],
        ]);
    }

    public function createAction(): ViewModel|Response
    {
        $request = $this->getRequest();
        $form = clone $this->categoryForm;

        if (!$request instanceof Request || !$request->isPost()) {
            $form->setData([
                'name' => '',
                'description' => '',
            ]);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => null,
                'form' => $form,
            ]))->setTemplate('application/category/form');
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if(!$form->isValid()) {
            $category = new Category();
            $category->setName((string) ($data['name'] ?? ''));
            $category->setDescription($data['description'] ?? null);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'form' => $form
            ]))->setTemplate('application/category/form');
        }

        $category = new Category();
        $validatedData = $form->getData();

        $category->setName((string) ($validatedData['name'] ?? ''));
        $category->setDescription($validatedData['description'] ?? null);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->redirect()->toRoute('category');
    }

    public function editAction(): ViewModel|Response
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        /** @var Category|null $category */
        $category = $this->entityManager->find(Category::class, $id);

        if (!$category instanceof Category) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $form = clone $this->categoryForm;

        if(!$request instanceof Request || !$request->isPost()) {
            $form->setData([
                'name' => $category->getName(),
                'description' => $category->getDescription(),
            ]);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'form' => $form,
            ]))->setTemplate('application/category/form');
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if (!$form->isValid()) {
            $category->setName((string) ($data['name'] ?? ''));
            $category->setDescription($data['description'] ?? null);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'form' => $form,
            ]))->setTemplate('application/category/form');
        }

        $validatedData = $form->getData();

        $category->setName((string) $validatedData['name']);
        $category->setDescription($validatedData['description'] ?? null);

        $this->entityManager->flush();

        return $this->redirect()->toRoute('category');
    }

    public function deleteAction(): Response
    {
        $id = $this->params()->fromRoute('id', 0);

        /** @var Category|null $category */
        $category = $this->entityManager->find(Category::class, $id);

        if ($category instanceof Category) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
        }

        return $this->redirect()->toRoute('category');
    }
}
