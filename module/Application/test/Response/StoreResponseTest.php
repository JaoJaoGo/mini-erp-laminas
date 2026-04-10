<?php

declare(strict_types=1);

namespace ApplicationTest\Response;

use Application\Entity\Product;
use Application\Entity\User;
use Application\Response\StoreResponse;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

class StoreResponseTest extends TestCase
{
    private StoreResponse $response;

    protected function setUp(): void
    {
        $this->response = new StoreResponse();
    }

    public function testIndexReturnsViewModelWithExpectedTemplateAndVariables(): void
    {
        $user = new User();
        $product = new Product();
        $products = [$product];
        $categories = [
            ['id' => 1, 'name' => 'Eletrônicos', 'total' => 3],
        ];
        $filters = ['name' => 'Teste', 'categoryId' => null];
        $pagination = ['total' => 1, 'page' => 1, 'perPage' => 12, 'totalPages' => 1];

        $viewModel = $this->response->index($user, $products, $categories, $filters, $pagination);

        self::assertInstanceOf(ViewModel::class, $viewModel);
        self::assertSame('application/store/index', $viewModel->getTemplate());
        self::assertSame($user, $viewModel->getVariable('user'));
        self::assertSame($products, $viewModel->getVariable('products'));
        self::assertSame($categories, $viewModel->getVariable('categories'));
        self::assertSame($filters, $viewModel->getVariable('filters'));
        self::assertSame($pagination, $viewModel->getVariable('pagination'));
    }

    public function testViewReturnsViewModelWithExpectedTemplateAndProduct(): void
    {
        $user = new User();
        $product = new Product();
        $relatedProducts = [];

        $viewModel = $this->response->view($user, $product, $relatedProducts);

        self::assertInstanceOf(ViewModel::class, $viewModel);
        self::assertSame('application/store/view', $viewModel->getTemplate());
        self::assertSame($user, $viewModel->getVariable('user'));
        self::assertSame($product, $viewModel->getVariable('product'));
        self::assertSame($relatedProducts, $viewModel->getVariable('relatedProducts'));
    }

    public function testCreateFiltersReturnsExpectedArray(): void
    {
        $filters = $this->response->createFilters('Notebook', 5);

        self::assertSame([
            'name' => 'Notebook',
            'categoryId' => 5,
            'sort' => 'latest',
            'inStock' => false,
        ], $filters);
    }

    public function testCreatePaginationReturnsExpectedArray(): void
    {
        $pagination = $this->response->createPagination(25, 2, 12, 3);

        self::assertSame([
            'total' => 25,
            'page' => 2,
            'perPage' => 12,
            'totalPages' => 3,
        ], $pagination);
    }
}
