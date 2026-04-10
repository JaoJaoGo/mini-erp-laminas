<?php

declare(strict_types=1);

namespace Application\Repository;

use Application\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ProductRepository extends EntityRepository
{
    /**
     * @return array{
     *      items: list<Product>,
     *      total: int,
     *      page: int,
     *      perPage: int,
     *      totalPages: int
     * }
     */
    public function findFilteredPaginated(
        string $name = '',
        string $category = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.id', 'DESC')
            ->distinct();

        if ($name !== '') {
            $qb->andWhere('p.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        if ($category !== '') {
            $qb->andWhere('c.name LIKE :category')->setParameter('category', '%' . $category . '%');
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

        /** @var list<Product> $items */
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
     * @return array{
     *      items: list<Product>,
     *      total:int,
     *      page: int,
     *      perPage: int,
     *      totalPages: int
     * }
     */
    public function findStorePaginated(
        string $name = '',
        ?int $categoryId = null,
        int $page = 1,
        int $perPage = 12
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.isActive = true')
            ->orderBy('p.id', 'DESC')
            ->distinct();
        
        if ($name !== '') {
            $qb->andWhere('p.name LIKE :name')->setParameter('name', '%' . $name . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')->setParameter('categoryId', $categoryId);
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

        /** @var list<Product> $items */
        $items = iterator_to_array($paginator->getIterator());

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function findActiveById(int $id): ?Product
    {
        $product = $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $product instanceof Product ? $product : null;
    }

    public function findStoreActiveById(int $id): ?Product
    {
        $product = $this->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->andWhere('p.id = :id')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.isActive = true')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $product instanceof Product ? $product : null;
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
            ->andWhere('p.deletedAt IS NULL')
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