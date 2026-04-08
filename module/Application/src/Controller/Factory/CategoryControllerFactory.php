<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\CategoryController;
use Application\Form\CategoryForm;
use Application\Response\CategoryResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CategoryControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CategoryController
    {
        return new CategoryController(
            $container->get(CategoryService::class),
            $container->get(CategoryResponse::class),
            $container->get(AuthService::class),
            $container->get(CategoryForm::class),
        );
    }
}