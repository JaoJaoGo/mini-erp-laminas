<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\AuthController;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use Psr\Container\ContainerInterface;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container): AuthController
    {
        return new AuthController(
            $container->get(AuthService::class),
            $container->get(LoginForm::class),
        );
    }
}