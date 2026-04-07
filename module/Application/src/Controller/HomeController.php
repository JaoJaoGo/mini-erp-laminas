<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Entity\Category;
use Application\Entity\Product;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class HomeController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly EntityManager $entityManager,
    ) { }

    public function homeAction()
    {
        $totalCategories = (int) $this->entityManager->getRepository(Category::class)->count([]);

        $totalProducts = (int) $this->entityManager->getRepository(Product::class)->count([]);

        return new ViewModel([
            'user' => $this->authService->getAuthenticatedUser(),
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
        ]);
    }
}
