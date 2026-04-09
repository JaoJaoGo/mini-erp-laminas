<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\ProductController;
use Application\Entity\Category;
use Application\Entity\Product;
use Application\Form\ProductForm;
use Application\Response\ProductResponse;
use Application\Service\AuthService;
use Application\Service\ProductService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class ProductControllerTest extends TestCase
{
    private ProductService $productService;
    private ProductResponse $productResponse;
    private AuthService $authService;
    private ProductForm $productForm;
    private ProductController $controller;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->productResponse = new ProductResponse();
        $this->authService = $this->createMock(AuthService::class);
        $this->productForm = new ProductForm();

        $this->controller = new ProductController(
            $this->productService,
            $this->productResponse,
            $this->authService,
            $this->productForm,
        );

        $this->controller->setPluginManager($this->createPluginManager());
        $this->controller->setEvent(new MvcEvent());
    }

    public function testIndexActionUsesFilters(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $product = new Product();
        $product->setName('Produto');

        $this->productService->expects(self::once())
            ->method('getFilteredProductsPaginated')
            ->with('Produto', 'Eletrônicos', 1, 10)
            ->willReturn([
                'items' => [$product],
                'total' => 1,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 1,
            ]);

        $request = new Request();
        $request->setQuery(new Parameters(['name' => 'Produto', 'category' => 'Eletrônicos']));
        $this->setControllerRequest($request);

        $result = $this->controller->indexAction();

        self::assertSame([$product], $result->getVariable('products'));
        self::assertSame('Produto', $result->getVariable('filters')['name']);
        self::assertSame('Eletrônicos', $result->getVariable('filters')['category']);
    }

    public function testCreateActionGetReturnsFormView(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->method('getCategoriesForForm')->willReturn([]);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertSame([], $result->getVariable('categories'));
    }

    public function testCreateActionPostValidRedirects(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->method('normalizeCategoryIds')->willReturn(['1']);
        $this->productService->expects(self::once())
            ->method('create')
            ->willReturn(new Product());

        $form = clone $this->productForm;
        $data = [
            'name' => 'Cadeira',
            'description' => 'Uma cadeira confortável',
            'price' => '99,90',
            'stock' => '10',
            'isActive' => '1',
            'categories' => ['1'],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testCreateActionPostInvalidReturnsFormView(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('appendCategoryValidationError');

        $form = clone $this->productForm;
        $data = [
            'name' => '',
            'description' => 'Uma cadeira confortável',
            'price' => 'abc',
            'stock' => '-1',
            'isActive' => '1',
            'categories' => ['1'],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testEditActionGetReturnsFormViewWhenProductExists(): void
    {
        $product = new Product();
        $product->setName('Editar');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(789)
            ->willReturn($product);

        $this->productService->method('getCategoriesForForm')->willReturn([]);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 789]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertArrayHasKey('product', $result->getVariables());
    }

    public function testEditActionGetReturns404WhenProductNotFound(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new Response();
        $this->setControllerRequest($request);
        $event = $this->createRouteEvent(['id' => 999]);
        $event->setResponse($response);
        $this->controller->setEvent($event);

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
    }

    public function testEditActionPostValidRedirectsAndUpdates(): void
    {
        $product = new Product();
        $product->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(789)
            ->willReturn($product);

        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('normalizeCategoryIds')
            ->willReturn(['1']);

        $this->productService->expects(self::once())
            ->method('update')
            ->with($product, self::anything(), ['1']);

        $form = clone $this->productForm;
        $data = [
            'name' => 'Atualizado',
            'description' => 'Descrição atualizada',
            'price' => '149,90',
            'stock' => '20',
            'isActive' => '1',
            'categories' => ['1'],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 789]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testEditActionPostInvalidReturnsFormViewWithValidationErrors(): void
    {
        $product = new Product();
        $product->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(789)
            ->willReturn($product);

        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('normalizeCategoryIds')
            ->willReturn([]);

        $this->productService->expects(self::once())
            ->method('appendCategoryValidationError');

        $form = clone $this->productForm;
        $data = [
            'name' => '',
            'description' => 'Sem nome e preço',
            'price' => 'invalid',
            'stock' => '999',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 789]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testDeleteActionRedirectsWhenProductExists(): void
    {
        $product = new Product();
        $product->setName('Remover');

        $this->productService->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($product);

        $this->productService->expects(self::once())
            ->method('delete')
            ->with($product);

        $this->controller->setEvent($this->createRouteEvent(['id' => 42]));

        $result = $this->controller->deleteAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testCreateActionPostWithImageUploadRedirects(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->method('normalizeCategoryIds')->willReturn(['1']);
        $this->productService->expects(self::once())
            ->method('create')
            ->with(self::anything(), ['1'], self::isInstanceOf(\Laminas\Http\PhpEnvironment\Request::class))
            ->willReturn(new Product());

        $form = clone $this->productForm;
        $data = [
            'name' => 'Produto com Imagem',
            'description' => 'Descrição',
            'price' => '99,90',
            'stock' => '10',
            'isActive' => '1',
            'categories' => ['1'],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testCreateActionPostCatchesRuntimeExceptionFromImageUpload(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->method('normalizeCategoryIds')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('create')
            ->will($this->throwException(new \RuntimeException('Falha ao enviar a imagem')));

        $form = clone $this->productForm;
        $data = [
            'name' => 'Produto',
            'description' => 'Descr',
            'price' => '50,00',
            'stock' => '5',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        $returnedForm = $result->getVariable('form');
        self::assertNotEmpty($returnedForm->get('image')->getMessages());
    }

    public function testEditActionPostWithImageUpdateRedirects(): void
    {
        $product = new Product();
        $product->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(789)
            ->willReturn($product);

        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('normalizeCategoryIds')
            ->willReturn(['1']);

        $this->productService->expects(self::once())
            ->method('update')
            ->with($product, self::anything(), ['1'], self::isInstanceOf(\Laminas\Http\PhpEnvironment\Request::class))
            ->willReturn($product);

        $form = clone $this->productForm;
        $data = [
            'name' => 'Atualizado',
            'description' => 'Nova descrição',
            'price' => '149,90',
            'stock' => '20',
            'isActive' => '1',
            'categories' => ['1'],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 789]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testEditActionPostCatchesRuntimeExceptionFromImageUpload(): void
    {
        $product = new Product();
        $product->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->productService->expects(self::once())
            ->method('findById')
            ->with(789)
            ->willReturn($product);

        $this->productService->method('getCategoriesForForm')->willReturn([]);
        $this->productService->expects(self::once())
            ->method('normalizeCategoryIds')
            ->willReturn([]);

        $this->productService->expects(self::once())
            ->method('update')
            ->will($this->throwException(new \RuntimeException('Formato de imagem inválido')));

        $form = clone $this->productForm;
        $data = [
            'name' => 'Produto',
            'description' => 'Descr',
            'price' => '100,00',
            'stock' => '15',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 789]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        $returnedForm = $result->getVariable('form');
        self::assertNotEmpty($returnedForm->get('image')->getMessages());
    }

    private function createPluginManager(): PluginManager
    {
        $serviceManager = new ServiceManager();
        $pluginManager = new PluginManager($serviceManager);

        $redirect = $this->createMock(Redirect::class);
        $redirect->method('toRoute')
            ->willReturnCallback(static function () {
                $response = new Response();
                $response->setStatusCode(302);

                return $response;
            });

        $pluginManager->setService('redirect', $redirect);

        return $pluginManager;
    }

    private function setControllerRequest(Request $request): void
    {
        $reflection = new \ReflectionProperty($this->controller, 'request');
        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, $request);
        $this->controller->getEvent()->setRequest($request);
    }

    private function createRouteEvent(array $routeParams): MvcEvent
    {
        $routeMatch = new RouteMatch($routeParams);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        return $event;
    }
}
