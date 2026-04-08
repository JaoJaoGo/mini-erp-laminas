<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Application\Service\ProductService;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProductServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ProductService
    {
        return new ProductService(
            $container->get(EntityManager::class),
            $container->get(ProductRepository::class),
            $container->get(CategoryRepository::class),
        );
    }
}