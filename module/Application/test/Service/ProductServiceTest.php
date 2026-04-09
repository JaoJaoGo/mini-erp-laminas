<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Category;
use Application\Entity\Product;
use Application\Form\ProductForm;
use Application\Repository\CategoryRepository;
use Application\Repository\ProductRepository;
use Application\Service\ProductService;
use Application\Service\ProductImageService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Laminas\Http\PhpEnvironment\Request;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private EntityManager $entityManager;
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private ProductImageService $productImageService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productImageService = $this->createMock(ProductImageService::class);
        $this->service = new ProductService(
            $this->entityManager,
            $this->productRepository,
            $this->categoryRepository,
            $this->productImageService,
        );
    }

    public function testNormalizeCategoryIdsFiltersAndDeduplicatesValues(): void
    {
        $result = $this->service->normalizeCategoryIds(['1', '2', '2', 'a', '', ' 3 ']);

        self::assertSame(['1', '2', '3'], $result);
    }

    public function testNormalizeMoneyValueConvertsBrazilianFormats(): void
    {
        self::assertSame('1234.56', $this->service->normalizeMoneyValue('1.234,56'));
        self::assertSame('99.90', $this->service->normalizeMoneyValue('99,90'));
        self::assertSame('12.50', $this->service->normalizeMoneyValue(' 12,50 '));
        self::assertSame('', $this->service->normalizeMoneyValue(''));
    }

    public function testFillEntityAssignsAllExpectedFields(): void
    {
        $product = new Product();

        $result = $this->service->fillEntity($product, [
            'name' => 'Cadeira',
            'description' => 'Cadeira ergonômica',
            'price' => '1.249,90',
            'stock' => '10',
            'isActive' => '0',
        ]);

        self::assertSame($product, $result);
        self::assertSame('Cadeira', $product->getName());
        self::assertSame('Cadeira ergonômica', $product->getDescription());
        self::assertSame('1249.90', $product->getPrice());
        self::assertSame(10, $product->getStock());
        self::assertFalse($product->isActive());
    }

    public function testFindCategoriesByIdsIgnoresMissingCategories(): void
    {
        $category1 = $this->createMock(Category::class);
        $category2 = $this->createMock(Category::class);

        $ids = [];

        $this->categoryRepository->expects(self::exactly(3))
            ->method('find')
            ->willReturnCallback(static function (int $id) use (&$ids, $category1, $category2) {
                $ids[] = $id;

                return match ($id) {
                    1 => $category1,
                    2 => null,
                    3 => $category2,
                    default => null,
                };
            });

        $result = $this->service->findCategoriesByIds(['1', '2', '3']);

        self::assertSame([$category1, $category2], $result);
    }

    public function testAppendCategoryValidationErrorAddsFormMessageWhenCategoryInvalid(): void
    {
        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $form = new ProductForm();
        $form->get('categories')->setMessages([]);

        $this->service->appendCategoryValidationError($form, ['99']);

        self::assertNotEmpty($form->get('categories')->getMessages());
    }

    public function testSyncCategoriesAddsValidCategories(): void
    {
        $category1 = new Category();
        $category2 = new Category();

        $ids = [];
        $this->categoryRepository->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use (&$ids, $category1, $category2) {
                $ids[] = $id;

                return $id === 1 ? $category1 : $category2;
            });

        $product = new Product();
        $product->addCategory(new Category());

        $this->service->syncCategories($product, ['1', '2']);

        self::assertSame(2, $product->getCategories()->count());
    }

    public function testCreatePersistsProductAndFlushes(): void
    {
        $request = $this->createMock(Request::class);

        $this->categoryRepository->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) {
                return new Category();
            });

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn('/uploads/products/image123.jpg');

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Product::class));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = $this->service->create(
            [
                'name' => 'Mesa',
                'description' => 'Mesa de escritório',
                'price' => '199,90',
                'stock' => '3',
                'isActive' => '1',
            ],
            ['1', '2'],
            $request,
        );

        self::assertSame('Mesa', $product->getName());
        self::assertSame('/uploads/products/image123.jpg', $product->getImagePath());
        self::assertSame(2, $product->getCategories()->count());
    }

    public function testCreateProductWithoutImagePathWhenNoFileUploaded(): void
    {
        $request = $this->createMock(Request::class);

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->willReturn(new Category());

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Product::class));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = $this->service->create(
            [
                'name' => 'Produto Sem Imagem',
                'description' => 'Sem imagem',
                'price' => '50,00',
                'stock' => '0',
                'isActive' => '0',
            ],
            ['1'],
            $request,
        );

        self::assertSame('Produto Sem Imagem', $product->getName());
        self::assertNull($product->getImagePath());
    }

    public function testUpdateFlushesProductChanges(): void
    {
        $request = $this->createMock(Request::class);
        $category1 = new Category();
        $category2 = new Category();

        $this->categoryRepository->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($category1, $category2) {
                return $id === 1 ? $category1 : $category2;
            });

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = new Product();

        $result = $this->service->update($product, [
            'name' => 'Mesa Atualizada',
            'description' => 'Mesa atualizada',
            'price' => '249,90',
            'stock' => '5',
            'isActive' => '1',
        ], ['1', '2'], $request);

        self::assertSame($product, $result);
        self::assertSame(2, $product->getCategories()->count());
    }

    public function testUpdateReplacesImageAndDeletesOldWhenNewImageProvided(): void
    {
        $request = $this->createMock(Request::class);
        $oldImagePath = '/uploads/products/old_image.jpg';

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn('/uploads/products/new_image.jpg');

        $this->productImageService->expects(self::once())
            ->method('delete')
            ->with($oldImagePath);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = new Product();
        $product->setImagePath($oldImagePath);

        $result = $this->service->update($product, [
            'name' => 'Produto Atualizado',
            'description' => 'Descr atualizada',
            'price' => '100,00',
            'stock' => '10',
            'isActive' => '1',
        ], [], $request);

        self::assertSame('/uploads/products/new_image.jpg', $result->getImagePath());
    }

    public function testUpdateKeepsOldImageWhenNoNewImageProvided(): void
    {
        $request = $this->createMock(Request::class);
        $existingImagePath = '/uploads/products/existing_image.jpg';

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn(null);

        $this->productImageService->expects(self::never())
            ->method('delete');

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = new Product();
        $product->setImagePath($existingImagePath);

        $result = $this->service->update($product, [
            'name' => 'Sem Mudança de Imagem',
            'description' => 'Mesma imagem',
            'price' => '79,90',
            'stock' => '15',
            'isActive' => '1',
        ], [], $request);

        self::assertSame($existingImagePath, $result->getImagePath());
    }
    public function testCreateProductWithoutCategoriesOnlyPersistsProduct(): void
    {
        $request = $this->createMock(Request::class);

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Product::class));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = $this->service->create([
            'name' => 'Produto Sem Categorias',
            'description' => 'Sem categorias',
            'price' => '50,00',
            'stock' => '0',
            'isActive' => '0',
        ], [], $request);

        self::assertSame('Produto Sem Categorias', $product->getName());
        self::assertSame(0, $product->getCategories()->count());
    }

    public function testUpdateRemovesOldCategoriesAndAddsNewOnes(): void
    {
        $request = $this->createMock(Request::class);
        $oldCategory = new Category();
        $newCategory = new Category();

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn($newCategory);

        $this->productImageService->expects(self::once())
            ->method('uploadFromRequest')
            ->with($request)
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $product = new Product();
        $product->addCategory($oldCategory);

        self::assertSame(1, $product->getCategories()->count());

        $this->service->update($product, [
            'name' => 'Produto',
            'description' => 'Description',
            'price' => '100,00',
            'stock' => '10',
            'isActive' => '1',
        ], ['999'], $request);

        self::assertSame(1, $product->getCategories()->count());
        self::assertTrue($product->getCategories()->contains($newCategory));
    }

    public function testGetCategoriesForFormReturnsArrayOfStdObjects(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $category1 = new Category();
        $category1->setName('Eletrônicos');

        $category2 = new Category();
        $category2->setName('Móveis');

        $this->categoryRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $qb->expects(self::once())->method('orderBy')->with('c.name', 'ASC')->willReturn($qb);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);
        $query->expects(self::once())->method('getResult')->willReturn([$category1, $category2]);

        $result = $this->service->getCategoriesForForm();

        self::assertCount(2, $result);
        self::assertInstanceOf(Category::class, $result[0]);
        self::assertSame('Eletrônicos', $result[0]->getName());
        self::assertInstanceOf(Category::class, $result[1]);
        self::assertSame('Móveis', $result[1]->getName());
    }

    public function testGetCategoriesForFormReturnsEmptyArrayWhenNoCategoriesExist(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->categoryRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $qb->expects(self::once())->method('orderBy')->with('c.name', 'ASC')->willReturn($qb);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);
        $query->expects(self::once())->method('getResult')->willReturn([]);

        $result = $this->service->getCategoriesForForm();

        self::assertSame([], $result);
    }

    public function testFindFilteredProductsByName(): void
    {
        $product1 = new Product();
        $product1->setName('Cadeira Gamer');

        $this->productRepository->expects(self::once())
            ->method('findFiltered')
            ->with('Cadeira', '')
            ->willReturn([$product1]);

        $result = $this->service->getFilteredProducts('Cadeira', '');

        self::assertCount(1, $result);
        self::assertSame($product1, $result[0]);
    }

    public function testFindFilteredProductsByCategory(): void
    {
        $product1 = new Product();
        $product1->setName('Produto');

        $this->productRepository->expects(self::once())
            ->method('findFiltered')
            ->with('', 'Eletrônicos')
            ->willReturn([$product1]);

        $result = $this->service->getFilteredProducts('', 'Eletrônicos');

        self::assertCount(1, $result);
    }

    public function testDeleteRemovesProductAndImageFromDatabase(): void
    {
        $imagePath = '/uploads/products/image123.jpg';
        $product = new Product();
        $product->setImagePath($imagePath);

        $this->productImageService->expects(self::once())
            ->method('delete')
            ->with($imagePath);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with($product);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->service->delete($product);
    }

    public function testDeleteRemovesProductWhenNoImageAttached(): void
    {
        $product = new Product();

        $this->productImageService->expects(self::once())
            ->method('delete')
            ->with(null);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with($product);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->service->delete($product);
    }

    public function testFindByIdReturnsProductWhenExists(): void
    {
        $product = new Product();

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($product);

        $result = $this->service->findById(42);

        self::assertSame($product, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->findById(999);

        self::assertNull($result);
    }

    public function testNormalizeCategoryIdsHandlesEmptyArray(): void
    {
        $result = $this->service->normalizeCategoryIds([]);

        self::assertSame([], $result);
    }

    public function testNormalizeMoneyValueHandlesAlreadyFormattedNumbers(): void
    {
        self::assertSame('50.00', $this->service->normalizeMoneyValue('50.00'));
        self::assertSame('1000.00', $this->service->normalizeMoneyValue('1000.00'));
    }

    public function testAppendCategoryValidationErrorDoesNothingWhenCategoriesValid(): void
    {
        $category = new Category();

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $form = new ProductForm();
        $form->get('categories')->setMessages([]);

        $this->service->appendCategoryValidationError($form, ['1']);

        self::assertEmpty($form->get('categories')->getMessages());
    }
}

