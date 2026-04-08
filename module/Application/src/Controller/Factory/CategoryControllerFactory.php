<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\CategoryController;
use Application\Service\AuthService;
use Application\Form\CategoryForm;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

class CategoryControllerFactory
{
    public function __invoke(ContainerInterface $container): CategoryController
    {
        return new CategoryController(
            $container->get(EntityManager::class),
            $container->get(AuthService::class),
            $container->get(CategoryForm::class),
        );
    }
}