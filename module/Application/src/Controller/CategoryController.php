<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Category;
use Application\Service\AuthService;
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

        if (!$request instanceof Request || !$request->isPost()) {
            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => null,
                'errors' => [],
            ]))->setTemplate('application/category/form');
        }

        $data = $request->getPost()->toArray();
        $errors = $this->validateCategoryData($data);

        if($errors !== []) {
            $category = new Category();
            $category->setName((string) ($data['name'] ?? ''));
            $category->setDescription($data['description'] ?? null);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'errors' => $errors,
            ]))->setTemplate('application/category/form');
        }

        $category = new Category();
        $category->setName((string) ($data['name'] ?? ''));
        $category->setDescription($data['description'] ?? null);

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

        if(!$request instanceof Request || !$request->isPost()) {
            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'errors' => [],
            ]))->setTemplate('application/category/form');
        }

        $data = $request->getPost()->toArray();
        $errors = $this->validateCategoryData($data);

        if ($errors !== []) {
            $category->setName((string) ($data['name'] ?? ''));
            $category->setDescription($data['description'] ?? null);

            return (new ViewModel([
                'user' => $this->authService->getAuthenticatedUser(),
                'category' => $category,
                'errors' => $errors,
            ]))->setTemplate('application/category/form');
        }

        $category->setName((string) $data['name']);
        $category->setDescription($data['description'] ?? null);

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

    private function validateCategoryData(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            $errors[] = 'O nome da categoria é obrigatório.';
        }

        if (mb_strlen($name) > 150) {
            $errors[] = "O nome da categoria deve ter no máximo 150 caracteres.";
        }

        return $errors;
    }
}
