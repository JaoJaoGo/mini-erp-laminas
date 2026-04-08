<?php

declare(strict_types=1);

namespace Application\Repository\Factory;

use Application\Entity\Product;
use Application\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProductRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ProductRepository
    {
        /** @var ProductRepository $repository */
        $repository = $container
            ->get(EntityManager::class)
            ->getRepository(Product::class);

        return $repository;
    }
}