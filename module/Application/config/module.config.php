<?php

declare(strict_types=1);

namespace Application;

use Application\Controller\AuthController;
use Application\Controller\Factory\AuthControllerFactory;
use Application\Controller\Factory\HomeControllerFactory;
use Application\Controller\Factory\CategoryControllerFactory;
use Application\Controller\Factory\ProductControllerFactory;
use Application\Controller\HomeController;
use Application\Controller\CategoryController;
use Application\Controller\ProductController;
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

            'category' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/categories[/:action]',
                    'defaults' => [
                        'controller' => CategoryController::class,
                        'action' => 'index'
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/view',
                            'defaults' => [
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/edit',
                            'defaults' => [
                                'action' => 'edit',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                ],
            ],

            'product' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/products[/:action]',
                    'defaults' => [
                        'controller' => ProductController::class,
                        'action' => 'index'
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/view',
                            'defaults' => [
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/edit',
                            'defaults' => [
                                'action' => 'edit',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id/delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*',
                            ],
                        ],
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
            CategoryController::class => CategoryControllerFactory::class,
            ProductController::class => ProductControllerFactory::class,
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
            'layout/listing-table' => __DIR__ . '/../view/layout/listing-table.phtml',
            'layout/listing-toolbar' => __DIR__ . '/../view/layout/listing-toolbar.phtml',
            'layout/modal-delete' => __DIR__ . '/../view/layout/modal-delete.phtml',
            'application/auth/login' => __DIR__ . '/../view/application/auth/login.phtml',
            'application/home/home' => __DIR__ . '/../view/application/home/home.phtml',
            'application/category/index' => __DIR__ . '/../view/application/category/index.phtml',
            'application/category/form' => __DIR__ . '/../view/application/category/form.phtml',
            'application/product/index' => __DIR__ . '/../view/application/product/index.phtml',
            'application/product/form' => __DIR__ . '/../view/application/product/form.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];