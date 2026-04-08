<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\CategoryForm;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class CategoryFormTest extends TestCase
{
    public function testCategoryNameIsRequired(): void
    {
        $form = new CategoryForm();
        $data = [
            'name' => '',
            'description' => 'Descrição',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $form->setData(new Parameters($data));

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('name', $form->getMessages());
    }

    public function testCategoryNameCannotExceedMaxLength(): void
    {
        $form = new CategoryForm();
        $data = [
            'name' => str_repeat('A', 151),
            'description' => 'Descrição',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $form->setData(new Parameters($data));

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('name', $form->getMessages());
    }
}
