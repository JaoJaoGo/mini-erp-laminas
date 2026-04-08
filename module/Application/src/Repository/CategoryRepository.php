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
}