<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\StoreController;
use Application\Response\StoreResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Application\Service\ProductService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class StoreControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): StoreController
    {
        return new StoreController(
            $container->get(ProductService::class),
            $container->get(CategoryService::class),
            $container->get(StoreResponse::class),
            $container->get(AuthService::class),
        );
    }
}