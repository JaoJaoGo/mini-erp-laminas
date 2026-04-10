<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * Formulário de cadastro de usuário.
 *
 * O RegisterForm é utilizado pelo AuthController para validar os dados de registro
 * de novos usuários. Ele inclui campos de nome, e-mail, senha, confirmação de senha
 * e token CSRF.
 *
 * Campos do formulário:
 * - name: Nome do usuário, obrigatório.
 * - email: E-mail do usuário, obrigatório e em formato válido.
 * - password: Senha do usuário, obrigatória.
 * - password_confirmation: Confirmação da senha, obrigatória e deve ser igual à senha.
 * - csrf: Token CSRF para proteção contra falsificação.
 * - submit: Botão de envio do formulário.
 *
 * Validações:
 * - name: obrigatório, trim, mínimo de 3 e máximo de 120 caracteres.
 * - email: obrigatório, trim, lowercase e formato de e-mail válido.
 * - password: obrigatório, mínimo de 6 caracteres.
 * - password_confirmation: obrigatório e idêntico ao campo password.
 */
class RegisterForm extends Form
{
    public function __construct(string $name = 'register-form')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Nome',
            ],
            'attributes' => [
                'id' => 'name',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => 'E-mail',
            ],
            'attributes' => [
                'id' => 'email',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Password::class,
            'options' => [
                'label' => 'Senha',
            ],
            'attributes' => [
                'id' => 'password',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'password_confirmation',
            'type' => Password::class,
            'options' => [
                'label' => 'Confirmar senha',
            ],
            'attributes' => [
                'id' => 'password_confirmation',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'csrf',
            'type' => Csrf::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Criar conta',
            ],
        ]);

        $this->setInputFilter($this->createInputFilter());
    }

    private function createInputFilter(): InputFilter
    {
        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'name',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'O nome é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 3,
                        'max' => 120,
                        'messages' => [
                            StringLength::TOO_SHORT => 'O nome deve ter pelo menos 3 caracteres.',
                            StringLength::TOO_LONG => 'O nome deve ter no máximo 120 caracteres',
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StringToLower'],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'O e-mail é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => EmailAddress::class,
                    'options' => [
                        'messages' => [
                            EmailAddress::INVALID_FORMAT => 'Informe um e-mail válido.',
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'password',
            'required' => true,
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'A senha é obrigatória.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 6,
                        'max' => 255,
                        'messages' => [
                            StringLength::TOO_SHORT => 'A senha deve ter pelo menos 6 caracteres',
                            StringLength::TOO_LONG => 'A senha deve ter no máximo 255 caracteres',
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'password_confirmation',
            'required' => true,
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'A confirmação de senha é obrigatória.',
                        ],
                    ],
                ],
                [
                    'name' => Identical::class,
                    'options' => [
                        'token' => 'password',
                        'messages' => [
                            Identical::NOT_SAME => 'A confirmação de senha deve ser igual à senha.',
                        ],
                    ],
                ],
            ],
        ]);

        return $inputFilter;
    }
}