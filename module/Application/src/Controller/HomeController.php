<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class HomeController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $authService,
    ) { }

    public function homeAction()
    {
        return new ViewModel([
            'user' => $this->authService->getAuthenticatedUser(),
        ]);
    }
}
