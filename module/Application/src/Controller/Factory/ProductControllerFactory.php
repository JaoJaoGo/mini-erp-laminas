<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Service\ProductService;
use Application\Response\ProductResponse;
use Application\Service\AuthService;
use Application\Form\ProductForm;
use Application\Controller\ProductController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProductControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ProductController
    {
        return new ProductController(
            $container->get(ProductService::class),
            $container->get(ProductResponse::class),
            $container->get(AuthService::class),
            $container->get(ProductForm::class),
        );
    }
}