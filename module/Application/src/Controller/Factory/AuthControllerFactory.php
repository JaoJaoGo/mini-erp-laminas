<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\AuthController;
use Application\Form\LoginForm;
use Application\Form\RegisterForm;
use Application\Service\AuthService;
use Application\Service\UserService;
use Psr\Container\ContainerInterface;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container): AuthController
    {
        return new AuthController(
            $container->get(AuthService::class),
            $container->get(LoginForm::class),
            $container->get(RegisterForm::class),
            $container->get(UserService::class),
        );
    }
}