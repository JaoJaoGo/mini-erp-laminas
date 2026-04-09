<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\CategoryController;
use Application\Entity\Category;
use Application\Form\CategoryForm;
use Application\Response\CategoryResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class CategoryControllerTest extends TestCase
{
    private CategoryService $categoryService;
    private CategoryResponse $categoryResponse;
    private AuthService $authService;
    private CategoryForm $categoryForm;
    private CategoryController $controller;

    protected function setUp(): void
    {
        $this->categoryService = $this->createMock(CategoryService::class);
        $this->categoryResponse = new CategoryResponse();
        $this->authService = $this->createMock(AuthService::class);
        $this->categoryForm = new CategoryForm();

        $this->controller = new CategoryController(
            $this->categoryService,
            $this->categoryResponse,
            $this->authService,
            $this->categoryForm,
        );

        $this->controller->setPluginManager($this->createPluginManager());
        $this->controller->setEvent(new MvcEvent());
    }

    public function testIndexActionUsesQueryNameFilter(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $category = new Category();
        $category->setName('Teste');

        $this->categoryService->expects(self::once())
            ->method('getFilteredCategoriesPaginated')
            ->with('Teste', 1, 10)
            ->willReturn([
                'items' => [$category],
                'total' => 1,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 1,
            ]);

        $request = new Request();
        $request->setQuery(new Parameters(['name' => 'Teste']));
        $this->setControllerRequest($request);

        $result = $this->controller->indexAction();

        self::assertSame([$category], $result->getVariable('categories'));
        self::assertSame('Teste', $result->getVariable('filters')['name']);
    }

    public function testCreateActionGetReturnsFormView(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);

        $result = $this->controller->createAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testCreateActionPostInvalidReturnsFormView(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->method('createEmpty')->willReturn(new Category());
        $this->categoryService->method('fillEntity')
            ->willReturnCallback(static fn (Category $category): Category => $category);

        $form = clone $this->categoryForm;

        $data = [
            'name' => '',
            'description' => 'Descrição',
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

    public function testCreateActionPostValidRedirects(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->expects(self::once())
            ->method('create')
            ->willReturn(new Category());

        $form = clone $this->categoryForm;

        $data = [
            'name' => 'Nova Categoria',
            'description' => 'Descrição da categoria',
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

    public function testEditActionGetReturnsFormViewWhenCategoryExists(): void
    {
        $category = new Category();
        $category->setName('Editar');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->expects(self::once())
            ->method('findById')
            ->with(456)
            ->willReturn($category);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 456]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertArrayHasKey('category', $result->getVariables());
    }

    public function testEditActionGetReturns404WhenCategoryNotFound(): void
    {
        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->expects(self::once())
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
        $category = new Category();
        $category->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->expects(self::once())
            ->method('findById')
            ->with(456)
            ->willReturn($category);

        $this->categoryService->expects(self::once())
            ->method('update')
            ->with($category, self::callback(static function (array $data): bool {
                return $data['name'] === 'Atualizado' && $data['description'] === 'Nova descrição';
            }));

        $form = clone $this->categoryForm;
        $data = [
            'name' => 'Atualizado',
            'description' => 'Nova descrição',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 456]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testEditActionPostInvalidReturnsFormViewWithErrors(): void
    {
        $category = new Category();
        $category->setName('Original');

        $this->authService->method('getAuthenticatedUser')->willReturn(null);
        $this->categoryService->expects(self::once())
            ->method('findById')
            ->with(456)
            ->willReturn($category);

        $this->categoryService->expects(self::once())
            ->method('fillEntity')
            ->willReturn($category);

        $form = clone $this->categoryForm;
        $data = [
            'name' => '',
            'description' => 'Sem nome',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);
        $this->controller->setEvent($this->createRouteEvent(['id' => 456]));

        $result = $this->controller->editAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testDeleteActionRedirectsAndDeletesCategory(): void
    {
        $category = new Category();
        $category->setName('Remover');

        $this->categoryService->expects(self::once())
            ->method('findById')
            ->with(123)
            ->willReturn($category);

        $this->categoryService->expects(self::once())
            ->method('delete')
            ->with($category);

        $this->controller->setEvent($this->createRouteEvent(['id' => 123]));

        $result = $this->controller->deleteAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
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
