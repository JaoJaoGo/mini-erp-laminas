<?php

declare(strict_types=1);

namespace Application\Repository;

use Application\Entity\Product;
use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
    /**
     * @return list<Product>
     */
    public function findFiltered(string $name = '', string $category = ''): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.id', 'DESC')
            ->distinct();

        if ($name !== '') {
            $qb->andWhere('p.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        if ($category !== '') {
            $qb->andWhere('c.name LIKE :category')->setParameter('category', '%' . $category . '%');
        }

        /** @var list<Product> $products */
        $products = $qb->getQuery()->getResult();

        return $products;
    }
}