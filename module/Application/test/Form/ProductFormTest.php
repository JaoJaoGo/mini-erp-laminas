<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\ProductForm;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class ProductFormTest extends TestCase
{
    public function testValidProductDataIsAccepted(): void
    {
        $form = new ProductForm();
        $data = [
            'name' => 'Cadeira',
            'description' => 'Uma cadeira confortável',
            'price' => '129,90',
            'stock' => '5',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $form->setData(new Parameters($data));

        self::assertTrue($form->isValid());
    }

    public function testInvalidPriceIsRejected(): void
    {
        $form = new ProductForm();
        $data = [
            'name' => 'Cadeira',
            'description' => 'Uma cadeira confortável',
            'price' => 'valor inválido',
            'stock' => '5',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $form->setData(new Parameters($data));

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('price', $form->getMessages());
    }

    public function testNegativeStockIsRejected(): void
    {
        $form = new ProductForm();
        $data = [
            'name' => 'Cadeira',
            'description' => 'Uma cadeira confortável',
            'price' => '99,90',
            'stock' => '-1',
            'isActive' => '1',
            'categories' => [],
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $form->setData(new Parameters($data));

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('stock', $form->getMessages());
    }
}
