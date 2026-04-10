<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * Formulário para criação e edição de categorias.
 *
 * O CategoryForm é utilizado pelo CategoryController para renderizar e validar
 * os dados de categoria. Ele define campos para nome, descrição e token CSRF.
 *
 * Campos do formulário:
 * - name: Campo obrigatório para o nome da categoria.
 * - description: Campo opcional para descrição da categoria.
 * - csrf: Campo de token CSRF para proteção contra ataques de falsificação de solicitação.
 *
 * Validações:
 * - name: obrigatório, trim e tamanho máximo de 150 caracteres.
 * - description: trim opcional.
 */
class CategoryForm extends Form
{
    public function __construct(string $name = 'category-form')
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
            'name' => 'description',
            'type' => Textarea::class,
            'options' => [
                'label' => 'Descrição',
            ],
            'attributes' => [
                'id' => 'description',
                'rows' => 5,
            ],
        ]);

        $this->add([
            'name' => 'csrf',
            'type' => Csrf::class,
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
                            NotEmpty::IS_EMPTY => 'O nome da categoria é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'max' => 150,
                        'messages' => [
                            StringLength::TOO_LONG => 'O nome da categoria deve ter no máximo 150 caracteres.',
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'description',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
        ]);

        return $inputFilter;
    }
}