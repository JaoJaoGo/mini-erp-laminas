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

    /**
     * @return array{
     *      active: int,
     *      inactive: int
     * }
     */
    public function getActiveVsInactiveCount(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.isActive AS isActive, COUNT(p.id) AS total')
            ->groupBy('p.isActive')
            ->getQuery()
            ->getArrayResult();
        
        $result = [
            'active' => 0,
            'inactive' => 0,
        ];

        foreach ($rows as $row) {
            $isActive = (bool) $row['isActive'];
            $total = (int) $row['total'];
            
            if ($isActive) {
                $result['active'] = $total;
                continue;
            }

            $result['inactive'] = $total;
        }

        return $result;
    }
}