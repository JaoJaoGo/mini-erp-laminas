<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\UserService;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

class UserServiceFactory
{
    public function __invoke(ContainerInterface $container): UserService
    {
        return new UserService(
            $container->get(EntityManager::class),
        );
    }
}