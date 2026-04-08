<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Application\Service\MetricService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MetricServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): MetricService
    {
        return new MetricService(
            $container->get(CategoryRepository::class),
            $container->get(ProductRepository::class),
            $container->get(EntityManager::class),
        );
    }
}