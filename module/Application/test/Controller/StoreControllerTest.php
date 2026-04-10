<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\StoreController;
use Application\Entity\Product;
use Application\Response\StoreResponse;
use Application\Service\AuthService;
use Application\Service\CategoryService;
use Application\Service\ProductService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

class StoreControllerTest extends TestCase
{
    private ProductService $productService;
    private CategoryService $categoryService;
    private StoreResponse $storeResponse;
    private AuthService $authService;
    private StoreController $controller;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->categoryService = $this->createMock(CategoryService::class);
        $this->storeResponse = $this->createMock(StoreResponse::class);
        $this->authService = $this->createMock(AuthService::class);

        $this->controller = new StoreController(
            $this->productService,
            $this->categoryService,
            $this->storeResponse,
            $this->authService,
        );
    }

    public function testIndexActionReturnsStoreIndexViewModel(): void
    {
        $products = [new Product()];
        $categories = [[
            'id' => 1,
            'name' => 'Eletrônicos',
            'total' => 3,
        ]];
        $pagination = [
            'total' => 1,
            'page' => 1,
            'perPage' => 12,
            'totalPages' => 1,
        ];
        $filters = [
            'name' => 'Teste',
            'categoryId' => null,
        ];

        $this->productService->expects(self::once())
            ->method('getStoreProductsPaginated')
            ->with('Teste', null, 1, 12)
            ->willReturn([
                'items' => $products,
                'total' => 1,
                'page' => 1,
                'perPage' => 12,
                'totalPages' => 1,
            ]);

        $this->categoryService->expects(self::once())
            ->method('getStoreCategoriesWithProductCount')
            ->willReturn($categories);

        $this->authService->expects(self::once())
            ->method('getAuthenticatedUser')
            ->willReturn(null);

        $this->storeResponse->expects(self::once())
            ->method('createFilters')
            ->with('Teste', null)
            ->willReturn($filters);

        $this->storeResponse->expects(self::once())
            ->method('createPagination')
            ->with(1, 1, 12, 1)
            ->willReturn($pagination);

        $this->storeResponse->expects(self::once())
            ->method('index')
            ->with(null, $products, $categories, $filters, $pagination)
            ->willReturn((new \Laminas\View\Model\ViewModel([
                'products' => $products,
                'categories' => $categories,
                'filters' => $filters,
                'pagination' => $pagination,
            ]))->setTemplate('application/store/index'));

        $request = new Request();
        $request->getQuery()->set('name', 'Teste');
        $request->getQuery()->set('category', '');
        $request->getQuery()->set('page', '1');
        $this->setControllerRequest($request);

        $result = $this->controller->indexAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertSame($products, $result->getVariable('products'));
        self::assertSame($categories, $result->getVariable('categories'));
        self::assertSame($filters, $result->getVariable('filters'));
        self::assertSame($pagination, $result->getVariable('pagination'));
    }

    public function testViewActionReturnsStoreViewModelWhenProductFound(): void
    {
        $product = new Product();

        $this->productService->expects(self::once())
            ->method('findStoreById')
            ->with(42)
            ->willReturn($product);

        $this->authService->expects(self::once())
            ->method('getAuthenticatedUser')
            ->willReturn(null);

        $this->storeResponse->expects(self::once())
            ->method('view')
            ->with(null, $product)
            ->willReturn(new \Laminas\View\Model\ViewModel());

        $this->controller->setEvent($this->createRouteEvent(['id' => 42]));

        $result = $this->controller->viewAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
    }

    public function testNormalizeCategoryIdReturnsIntegerWhenValueIsValid(): void
    {
        $method = new \ReflectionMethod(StoreController::class, 'normalizeCategoryId');
        $method->setAccessible(true);

        self::assertSame(5, $method->invoke($this->controller, '5'));
        self::assertSame(null, $method->invoke($this->controller, '0'));
        self::assertSame(null, $method->invoke($this->controller, 'abc'));
        self::assertSame(null, $method->invoke($this->controller, ''));
    }

    private function setControllerRequest(Request $request): void
    {
        $reflection = new \ReflectionProperty($this->controller, 'request');
        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, $request);

        $event = new MvcEvent();
        $event->setRequest($request);
        $this->controller->setEvent($event);
    }

    private function createRouteEvent(array $routeParams): MvcEvent
    {
        $routeMatch = new RouteMatch($routeParams);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        return $event;
    }
}
