<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\ProductController;
use Application\Service\AuthService;
use Application\Form\ProductForm;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

class ProductControllerFactory
{
    public function __invoke(ContainerInterface $container): ProductController
    {
        return new ProductController(
            $container->get(EntityManager::class),
            $container->get(AuthService::class),
            $container->get(ProductForm::class),
        );
    }
}