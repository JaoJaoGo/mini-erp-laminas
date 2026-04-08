<?php

declare(strict_types=1);

namespace Application\Repository\Factory;

use Application\Entity\Category;
use Application\Repository\CategoryRepository;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CategoryRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CategoryRepository
    {
        /** @var CategoryRepository $repository */
        $repository = $container
            ->get(EntityManager::class)
            ->getRepository(Category::class);

        return $repository;
    }
}