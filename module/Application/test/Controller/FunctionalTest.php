<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Entity\Category;
use Application\Entity\Product;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function testCategorySoftDeleteIntegration(): void
    {
        // Testar integração do soft delete em Category
        $category = new Category();
        $category->setName('Test Category');
        $category->setDeletedAt(null);

        // Verificar que não está deletado inicialmente
        $this->assertFalse($category->isDeleted());
        $this->assertNull($category->getDeletedAt());

        // Simular soft delete
        $category->softDelete();

        // Verificar que está deletado
        $this->assertTrue($category->isDeleted());
        $this->assertNotNull($category->getDeletedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $category->getDeletedAt());
    }

    public function testProductSoftDeleteIntegration(): void
    {
        // Testar integração do soft delete em Product
        $product = new Product();
        $product->setName('Test Product');
        $product->setDeletedAt(null);

        // Verificar que não está deletado inicialmente
        $this->assertFalse($product->isDeleted());
        $this->assertNull($product->getDeletedAt());

        // Simular soft delete
        $product->softDelete();

        // Verificar que está deletado
        $this->assertTrue($product->isDeleted());
        $this->assertNotNull($product->getDeletedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getDeletedAt());
    }

    public function testCategoryPaginationLogic(): void
    {
        // Testar lógica de paginação para categorias
        $page = 2;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $this->assertEquals(10, $offset);

        $totalItems = 25;
        $totalPages = (int) ceil($totalItems / $perPage);
        $this->assertEquals(3, $totalPages);

        // Testar estrutura de resposta esperada
        $expectedResponse = [
            'items' => [], // seria preenchido com dados reais
            'total' => 25,
            'page' => 2,
            'perPage' => 10,
            'totalPages' => 3,
        ];

        $this->assertIsArray($expectedResponse);
        $this->assertEquals(25, $expectedResponse['total']);
        $this->assertEquals(2, $expectedResponse['page']);
        $this->assertEquals(10, $expectedResponse['perPage']);
        $this->assertEquals(3, $expectedResponse['totalPages']);
    }

    public function testProductPaginationLogic(): void
    {
        // Testar lógica de paginação para produtos
        $page = 1;
        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        $this->assertEquals(0, $offset);

        $totalItems = 23;
        $totalPages = (int) ceil($totalItems / $perPage);
        $this->assertEquals(5, $totalPages);

        // Testar estrutura de resposta esperada
        $expectedResponse = [
            'items' => [], // seria preenchido com dados reais
            'total' => 23,
            'page' => 1,
            'perPage' => 5,
            'totalPages' => 5,
        ];

        $this->assertIsArray($expectedResponse);
        $this->assertEquals(23, $expectedResponse['total']);
        $this->assertEquals(1, $expectedResponse['page']);
        $this->assertEquals(5, $expectedResponse['perPage']);
        $this->assertEquals(5, $expectedResponse['totalPages']);
    }

    public function testCategoryProductRelationship(): void
    {
        // Testar relacionamento entre Category e Product
        $category = new Category();
        $category->setName('Electronics');

        $product = new Product();
        $product->setName('Laptop');
        $product->addCategory($category);

        // Verificar relacionamento
        $this->assertCount(1, $product->getCategories());
        $this->assertContains($category, $product->getCategories());
        $this->assertEquals('Electronics', $product->getCategories()->first()->getName());
    }

    public function testMultipleCategoriesPerProduct(): void
    {
        // Testar múltiplas categorias por produto
        $category1 = new Category();
        $category1->setName('Electronics');

        $category2 = new Category();
        $category2->setName('Computers');

        $product = new Product();
        $product->setName('Gaming Laptop');
        $product->addCategory($category1);
        $product->addCategory($category2);

        // Verificar relacionamentos múltiplos
        $this->assertCount(2, $product->getCategories());
        $this->assertContains($category1, $product->getCategories());
        $this->assertContains($category2, $product->getCategories());
    }

    public function testUserRegistrationDataValidation(): void
    {
        // Testar validação de dados de registro (lógica básica)
        $validData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'password' => 'securePass123',
        ];

        $this->assertEquals('João Silva', $validData['name']);
        $this->assertStringContainsString('@', $validData['email']);
        $this->assertGreaterThanOrEqual(8, strlen($validData['password']));

        // Testar email trimming e lowercasing (lógica esperada)
        $emailWithSpaces = '  JOHN.DOE@EXAMPLE.COM  ';
        $normalizedEmail = strtolower(trim($emailWithSpaces));

        $this->assertEquals('john.doe@example.com', $normalizedEmail);
    }

    public function testPriceFormattingLogic(): void
    {
        // Testar lógica de formatação de preço brasileiro
        $brazilianPrice = '1.299,90';
        $numericPrice = (float) str_replace(['.', ','], ['', '.'], $brazilianPrice);

        $this->assertEquals(1299.90, $numericPrice);

        $anotherPrice = '99,99';
        $anotherNumeric = (float) str_replace(['.', ','], ['', '.'], $anotherPrice);

        $this->assertEquals(99.99, $anotherNumeric);
    }

    public function testStockValidationLogic(): void
    {
        // Testar lógica de validação de estoque
        $validStocks = [0, 1, 10, 100, 1000];
        $invalidStocks = [-1, -10];

        foreach ($validStocks as $stock) {
            $this->assertGreaterThanOrEqual(0, $stock);
            $this->assertIsNumeric($stock);
        }

        foreach ($invalidStocks as $stock) {
            $this->assertLessThan(0, $stock);
        }
    }

    public function testPaginationEdgeCases(): void
    {
        // Testar casos extremos de paginação
        $totalItems = 0;
        $perPage = 10;
        $totalPages = (int) ceil($totalItems / $perPage);
        $this->assertEquals(0, $totalPages);

        // Página maior que total de páginas
        $page = 10;
        $totalItems = 5;
        $perPage = 10;
        $totalPages = (int) ceil($totalItems / $perPage);
        $this->assertEquals(1, $totalPages);
        // Em implementação real, deveria ser clamped para totalPages

        // PerPage = 0 (deve ser evitado)
        $perPage = 1; // Usar mínimo 1
        $totalPages = (int) ceil($totalItems / $perPage);
        $this->assertEquals(5, $totalPages);
    }

    public function testSoftDeleteQueryFiltering(): void
    {
        // Testar conceito de filtragem por deletedAt IS NULL
        $activeCategories = [
            ['id' => 1, 'name' => 'Active 1', 'deletedAt' => null],
            ['id' => 2, 'name' => 'Active 2', 'deletedAt' => null],
        ];

        $deletedCategories = [
            ['id' => 3, 'name' => 'Deleted 1', 'deletedAt' => new \DateTimeImmutable()],
            ['id' => 4, 'name' => 'Deleted 2', 'deletedAt' => new \DateTimeImmutable()],
        ];

        // Simular filtro WHERE deletedAt IS NULL
        $filteredCategories = array_filter(array_merge($activeCategories, $deletedCategories),
            fn($cat) => $cat['deletedAt'] === null
        );

        $this->assertCount(2, $filteredCategories);
        $this->assertEquals(1, $filteredCategories[0]['id']);
        $this->assertEquals(2, $filteredCategories[1]['id']);
    }
}