<?php

declare(strict_types=1);

namespace Application\Repository;

use Application\Entity\Category;
use Doctrine\ORM\EntityRepository;

class CategoryRepository extends EntityRepository
{
    /**
     * @return list<Category>
     */
    public function findFiltered(string $name = ''): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.id', 'DESC');

        if ($name !== '') {
            $qb->andWhere('c.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        /** @var list<Category> $categories */
        $categories = $qb->getQuery()->getResult();

        return $categories;
    }

    /**
     * @return array<int, array{name:string, total:int}>
     */
    public function getProductCountGroupedByCategory(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.name AS name, COUNT(p.id) AS total')
            ->leftJoin('c.products', 'p')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'name' => (string) $row['name'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }
}