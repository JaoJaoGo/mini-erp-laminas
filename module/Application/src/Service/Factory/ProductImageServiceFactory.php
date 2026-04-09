<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\ProductImageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ProductImageServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ProductImageService
    {
        $config = $container->get('config');
        $publicDirectory = $config['product_upload']['public_directory'] ?? 'public';

        return new ProductImageService($publicDirectory);
    }
}