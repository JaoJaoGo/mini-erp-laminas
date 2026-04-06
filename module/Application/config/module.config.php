<?php

declare(strict_types=1);

namespace Application;

use Application\Controller\AuthController;
use Application\Controller\Factory\AuthControllerFactory;
use Application\Controller\Factory\HomeControllerFactory;
use Application\Controller\HomeController;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],

            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],

            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => HomeController::class,
                        'action' => 'home',
                    ],
                ],
            ],

            'application' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/application[/:action]',
                    'defaults' => [
                        'controller' => HomeController::class,
                        'action' => 'home',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            HomeController::class => HomeControllerFactory::class,
            AuthController::class => AuthControllerFactory::class,
        ],
    ],

    'service_manager' => [
        'factories' => [
            AuthService::class => AuthServiceFactory::class,
            LoginForm::class => InvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
            'layout/header' => __DIR__ . '/../view/layout/header.phtml',
            'layout/footer' => __DIR__ . '/../view/layout/footer.phtml',
            'application/auth/login' => __DIR__ . '/../view/application/auth/login.phtml',
            'application/home/home' => __DIR__ . '/../view/application/home/home.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];