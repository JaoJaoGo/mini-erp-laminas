<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Repository\CategoryRepository;
use Application\Service\CategoryService;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CategoryServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CategoryService
    {
        return new CategoryService(
            $container->get(EntityManager::class),
            $container->get(CategoryRepository::class),
        );
    }
}