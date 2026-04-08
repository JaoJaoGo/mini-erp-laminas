<?php

declare(strict_types=1);

namespace Application\Response\Factory;

use Application\Response\CategoryResponse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CategoryResponseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CategoryResponse
    {
        return new CategoryResponse();
    }
}