<?php

declare(strict_types=1);

namespace Application\Response\Factory;

use Application\Response\ProductResponse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProductResponseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ProductResponse
    {
        return new ProductResponse();
    }
}