<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * Formulário de login utilizado para autenticar usuários no sistema. Este formulário inclui campos para email e senha, além de um token CSRF para proteção contra ataques de falsificação de solicitação entre sites. O formulário também define regras de validação para garantir que os dados inseridos sejam válidos, como verificar se o email é um formato válido e se a senha atende aos requisitos mínimos de comprimento.
 * O LoginForm é utilizado pelo AuthController para processar as tentativas de login dos usuários, garantindo que apenas usuários autenticados possam acessar áreas restritas do sistema. Ele é configurado para ser enviado via POST e inclui mensagens de erro personalizadas para orientar os usuários em caso de falha na validação dos dados.
 * 
 * Campos do formulário:
 * - email: Campo de email obrigatório, deve ser um formato válido de email.
 * - password: Campo de senha obrigatório, deve conter pelo menos 6 caracteres.
 * - csrf: Campo de token CSRF para proteção contra ataques de falsificação de solicitação
 * - submit: Botão de envio do formulário
 * 
 * Validações:
 * - email: Verifica se o campo não está vazio e se o formato é um email válido.
 * - password: Verifica se o campo não está vazio e se a senha tem pelo menos 6 caracteres.
 * 
 * O LoginForm é uma parte essencial do processo de autenticação, garantindo que os dados de login sejam validados corretamente antes de serem processados pelo AuthService para autenticar o usuário.
 */
class LoginForm extends Form
{
    public function __construct(string $name = 'login-form')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => 'Email',
            ],
            'attributes' => [
                'id' => 'email',
                'required' => true,
                'autocomplete' => 'email',
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
                'autocomplete' => 'current-password',
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
                'value' => 'Entrar',
            ],
        ]);

        $this->setInputFilter($this->createInputFilter());
    }

    private function createInputFilter(): InputFilter
    {
        $inputFilter = new InputFilter();

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
                            NotEmpty::IS_EMPTY => 'Informe o e-mail.',
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
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Informe a senha.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 6,
                        'messages' => [
                            StringLength::TOO_SHORT => 'A senha deve conter pelo menos 6 caracteres.',
                        ],
                    ],
                ],
            ],
        ]);

        return $inputFilter;
    }
}