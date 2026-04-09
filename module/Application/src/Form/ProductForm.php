<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\File;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Number;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\FileInput;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class ProductForm extends Form
{
    public function __construct(string $name = 'product-form')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');
        $this->setAttribute('enctype', 'multipart/form-data');

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
            'name' => 'image',
            'type' => File::class,
            'options' => [
                'label' => 'Imagem',
            ],
            'attributes' => [
                'id' => 'image',
                'accept' => '.jpg, .jpeg, .png, .webp,image/jpeg,image/png,image/webp',
            ],
        ]);

        $this->add([
            'name' => 'price',
            'type' => Text::class,
            'options' => [
                'label' => 'Preço',
            ],
            'attributes' => [
                'id' => 'price',
                'required' => true,
                'placeholder' => 'Ex: 99,90',
            ],
        ]);

        $this->add([
            'name' => 'stock',
            'type' => Number::class,
            'options' => [
                'label' => 'Estoque',
            ],
            'attributes' => [
                'id' => 'stock',
                'required' => true,
                'min' => '0',
                'step' => '1',
            ],
        ]);

        $this->add([
            'name' => 'isActive',
            'type' => Select::class,
            'options' => [
                'label' => 'Status',
                'value_options' => [
                    '1' => 'Ativo',
                    '0' => 'Inativo',
                ],
            ],
            'attributes' => [
                'id' => 'isActive',
            ],
        ]);

        $this->add([
            'name' => 'categories',
            'type' => Hidden::class,
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
                            NotEmpty::IS_EMPTY => 'O nome do produto é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'max' => 150,
                        'messages' => [
                            StringLength::TOO_LONG => 'O nome do produto deve ter no máximo 150 caracteres.',
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

        $fileInput = new FileInput('image');
        $fileInput->setRequired(false);
        $fileInput->getValidatorChain()
            ->attachByName('FileExtension', [
                'extension' => ['jpg', 'jpeg', 'png', 'webp'],
                'messages' => [
                    \Laminas\Validator\File\Extension::FALSE_EXTENSION => 'Envie uma imagem JPG, JPEG, PNG ou WEBP.',
                ],
            ])
            ->attachByName('FileMimeType', [
                'mimeType' => ['image/jpeg', 'image/png', 'image/webp'],
                'messages' => [
                    \Laminas\Validator\File\MimeType::FALSE_TYPE => 'O arquivo enviado não é uma imagem válida.',
                ],
            ])
            ->attachByName('FileSize', [
                'max' => '5MB',
                'messages' => [
                    \Laminas\Validator\File\Size::TOO_BIG => 'A imagem deve ter no máximo 5MB.',
                ],
            ]);

        $inputFilter->add($fileInput);

        $inputFilter->add([
            'name' => 'price',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'O preço do produto é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => Callback::class,
                    'options' => [
                        'messages' => [
                            Callback::INVALID_VALUE => 'Informe um preço válido.',
                        ],
                        'callback' => static function (mixed $value): bool {
                            $value = trim((string) $value);

                            if ($value === '') {
                                return false;
                            }

                            $normalized = str_replace(' ', '', $value);

                            if (str_contains($normalized, ',')) {
                                $normalized = str_replace('.', '', $normalized);
                                $normalized = str_replace(',', '.', $normalized);
                            }

                            return is_numeric($normalized);
                        },
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'stock',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'O estoque é obrigatório.',
                        ],
                    ],
                ],
                [
                    'name' => Callback::class,
                    'options' => [
                        'messages' => [
                            Callback::INVALID_VALUE => 'O estoque deve ser um número inteiro não negativo.',
                        ],
                        'callback' => static function (mixed $value): bool {
                            $value = trim((string) $value);

                            if ($value === '') {
                                return false;
                            }

                            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                                return false;
                            }

                            return (int) $value >= 0;
                        },
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'isActive',
            'required' => true,
        ]);

        $inputFilter->add([
            'name' => 'categories',
            'required' => false,
        ]);

        return $inputFilter;
    }
}