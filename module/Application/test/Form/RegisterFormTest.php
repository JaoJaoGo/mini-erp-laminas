<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\RegisterForm;
use PHPUnit\Framework\TestCase;

class RegisterFormTest extends TestCase
{
    private RegisterForm $form;

    protected function setUp(): void
    {
        $this->form = new RegisterForm();
    }

    public function testFormHasNameField(): void
    {
        self::assertTrue($this->form->has('name'));
    }

    public function testFormHasEmailField(): void
    {
        self::assertTrue($this->form->has('email'));
    }

    public function testFormHasPasswordField(): void
    {
        self::assertTrue($this->form->has('password'));
    }

    public function testFormHasPasswordConfirmationField(): void
    {
        self::assertTrue($this->form->has('password_confirmation'));
    }

    public function testNameIsRequired(): void
    {
        $this->form->setData([
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('name', $this->form->getMessages());
    }

    public function testNameMustHaveMinimumLength(): void
    {
        $this->form->setData([
            'name' => 'Jo',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('name', $this->form->getMessages());
    }

    public function testNameMustNotExceedMaximumLength(): void
    {
        $this->form->setData([
            'name' => str_repeat('a', 121),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('name', $this->form->getMessages());
    }

    public function testEmailIsRequired(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('email', $this->form->getMessages());
    }

    public function testEmailMustBeValid(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('email', $this->form->getMessages());
    }

    public function testPasswordIsRequired(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('password', $this->form->getMessages());
    }

    public function testPasswordMustHaveMinimumLength(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => 'test@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('password', $this->form->getMessages());
    }

    public function testPasswordConfirmationMustMatch(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        self::assertFalse($this->form->isValid());
        self::assertArrayHasKey('password_confirmation', $this->form->getMessages());
    }

    public function testValidDataIsAccepted(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'csrf' => $this->form->get('csrf')->getValue(),
        ]);

        self::assertTrue($this->form->isValid());
    }

    public function testFormTrimsAndLowercasesEmail(): void
    {
        $this->form->setData([
            'name' => 'João Silva',
            'email' => '  JOAO@EXAMPLE.COM  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'csrf' => $this->form->get('csrf')->getValue(),
        ]);

        self::assertTrue($this->form->isValid());
        $data = $this->form->getData();
        self::assertSame('joao@example.com', $data['email']);
    }
}
