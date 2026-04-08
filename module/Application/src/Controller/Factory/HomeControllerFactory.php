<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Service\AuthService;
use Application\Controller\HomeController;
use Application\Service\MetricService;
use Psr\Container\ContainerInterface;

class HomeControllerFactory
{
    public function __invoke(ContainerInterface $container): HomeController
    {
        return new HomeController(
            $container->get(MetricService::class),
            $container->get(AuthService::class),
        );
    }
}