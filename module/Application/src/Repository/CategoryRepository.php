<?php

declare(strict_types=1);

namespace Application\Repository;

use Application\Entity\Category;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query\Expr\Join;

class CategoryRepository extends EntityRepository
{
    /**
     * @return array{
     *      items: list<Category>,
     *      total: int,
     *      page: int,
     *      perPage: int,
     *      totalPages: int
     * }
     */
    public function findFilteredPaginated(
        string $name = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $qb = $this->createQueryBuilder('c')->andWhere('c.deletedAt IS NULL')->orderBy('c.id', 'DESC');

        if ($name !== '') {
            $qb->andWhere('c.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);

        $paginator = new Paginator($qb, true);
        $total = count($paginator);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;

            $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);

            $paginator = new Paginator($qb, true);
        }

        /** @var list<Category> $items */
        $items = iterator_to_array($paginator->getIterator());

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * @return list<array{id:int,name:string,total:int}>
     */
    public function findStoreCategoriesWithProductCount(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS id, c.name AS name, COUNT(DISTINCT p.id) AS total')
            ->innerJoin('c.products', 'p', Join::WITH, 'p.deletedAt IS NULL AND p.isActive = true')
            ->andWhere('c.deletedAt IS NULL')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }

    public function findActiveById(int $id): ?Category
    {
        $category = $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $category instanceof Category ? $category : null;
    }

    /**
     * @return array<int, array{name:string, total:int}>
     */
    public function getProductCountGroupedByCategory(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.name AS name, COUNT(p.id) AS total')
            ->leftJoin('c.products', 'p', Join::WITH, 'p.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL')
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