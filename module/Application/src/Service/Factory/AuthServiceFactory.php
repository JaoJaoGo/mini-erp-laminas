<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\AuthService;
use Doctrine\ORM\EntityManager;
use Laminas\Session\Container;
use Psr\Container\ContainerInterface;

class AuthServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthService
    {
        return new AuthService(
            $container->get(EntityManager::class),
            new Container('auth'),
        );
    }
}