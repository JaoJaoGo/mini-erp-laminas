<?php

/**
 * Configuração principal do módulo Application.
 *
 * Define rotas, controllers, serviço de injeção de dependências, opções de upload
 * de produto e templates de view para a aplicação.
 */
declare(strict_types=1);

namespace Application;

use Application\Controller\AuthController;
use Application\Controller\Factory\AuthControllerFactory;
use Application\Controller\Factory\HomeControllerFactory;
use Application\Controller\Factory\CategoryControllerFactory;
use Application\Controller\Factory\ProductControllerFactory;
use Application\Controller\Factory\StoreControllerFactory;
use Application\Controller\HomeController;
use Application\Controller\CategoryController;
use Application\Controller\ProductController;
use Application\Controller\StoreController;
use Application\Form\LoginForm;
use Application\Form\RegisterForm;
use Application\Form\CategoryForm;
use Application\Form\ProductForm;
use Application\Service\AuthService;
use Application\Service\UserService;
use Application\Service\MetricService;
use Application\Service\CategoryService;
use Application\Service\ProductService;
use Application\Service\ProductImageService;
use Application\Service\Factory\AuthServiceFactory;
use Application\Service\Factory\UserServiceFactory;
use Application\Service\Factory\MetricServiceFactory;
use Application\Service\Factory\CategoryServiceFactory;
use Application\Service\Factory\ProductServiceFactory;
use Application\Service\Factory\ProductImageServiceFactory;
use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Application\Repository\Factory\CategoryRepositoryFactory;
use Application\Repository\Factory\ProductRepositoryFactory;
use Application\Response\CategoryResponse;
use Application\Response\ProductResponse;
use Application\Response\StoreResponse;
use Application\Response\Factory\CategoryResponseFactory;
use Application\Response\Factory\ProductResponseFactory;
use Application\Response\Factory\StoreResponseFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    /**
     * Configuração de rotas do módulo Application.
     *
     * Define rotas públicas e privadas, incluindo as rotas de autenticação,
     * a loja pública, dashboard e os melhores recursos CRUD para categorias e produtos.
     */
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
            'register' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/register',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action' => 'register',
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

            'store' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/loja',
                    'defaults' => [
                        'controller' => StoreController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id',
                            'defaults' => [
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'id' => '[1-9][0-9]*'
                            ],
                        ],
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

    /**
     * Controllers e factories.
     *
     * Cada controller tem sua factory responsável por injetar dependências
     * necessárias para a execução das ações da aplicação.
     */
    'controllers' => [
        'factories' => [
            HomeController::class => HomeControllerFactory::class,
            AuthController::class => AuthControllerFactory::class,
            CategoryController::class => CategoryControllerFactory::class,
            ProductController::class => ProductControllerFactory::class,
            StoreController::class => StoreControllerFactory::class,
        ],
    ],

    /**
     * Serviço de injeção de dependências.
     *
     * Registra factories para Services, Repositories, Responses e Forms,
     * garantindo criação correta de instâncias com dependências necessárias.
     */
    'service_manager' => [
        'factories' => [
            AuthService::class => AuthServiceFactory::class,
            UserService::class => UserServiceFactory::class,
            MetricService::class => MetricServiceFactory::class,
            CategoryService::class => CategoryServiceFactory::class,
            CategoryRepository::class => CategoryRepositoryFactory::class,
            CategoryResponse::class => CategoryResponseFactory::class,
            ProductService::class => ProductServiceFactory::class,
            ProductImageService::class => ProductImageServiceFactory::class,
            ProductRepository::class => ProductRepositoryFactory::class,
            ProductResponse::class => ProductResponseFactory::class,
            LoginForm::class => InvokableFactory::class,
            RegisterForm::class => InvokableFactory::class,
            ProductForm::class => InvokableFactory::class,
            CategoryForm::class => InvokableFactory::class,
            StoreResponse::class => StoreResponseFactory::class,
        ],
    ],

    /**
     * Configurações específicas de upload de produto.
     *
     * Define o diretório público onde imagens de produto são gravadas.
     */
    'product_upload' => [
        'public_directory' => dirname(__DIR__, 3) . '/public',
    ],

    /**
     * Configuração de visualização.
     *
     * Define templates, paths e opções de exibição para erros, layouts e páginas da aplicação.
     */
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
            'layout/pagination' => __DIR__ . '/../view/layout/pagination.phtml',
            'layout/chart/pie-chart' => __DIR__ . '/../view/layout/chart/pie-chart.phtml',
            'application/auth/login' => __DIR__ . '/../view/application/auth/login.phtml',
            'application/auth/register' => __DIR__ . '/../view/application/auth/register.phtml',
            'application/store/index' => __DIR__ . '/../view/application/store/index.phtml',
            'application/store/view' => __DIR__ . '/../view/application/store/view.phtml',
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