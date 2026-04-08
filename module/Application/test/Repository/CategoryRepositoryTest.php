<?php

declare(strict_types=1);

namespace ApplicationTest\Repository;

use Application\Entity\Category;
use Application\Repository\CategoryRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class CategoryRepositoryTest extends TestCase
{
    public function testFindFilteredBuildsQueryWithNameFilter(): void
    {
        $category = new Category();
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->with('c.id', 'DESC')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('andWhere')
            ->with('c.name LIKE :name')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setParameter')
            ->with('name', '%Teste%')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$category]);

        self::assertSame([$category], $repository->findFiltered('Teste'));
    }

    public function testGetProductCountGroupedByCategoryMapsQueryResult(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $qb->expects(self::once())->method('select')->with('c.name AS name, COUNT(p.id) AS total')->willReturn($qb);
        $qb->expects(self::once())->method('leftJoin')->with('c.products', 'p')->willReturn($qb);
        $qb->expects(self::once())->method('groupBy')->with('c.id')->willReturn($qb);
        $qb->expects(self::once())->method('orderBy')->with('c.name', 'ASC')->willReturn($qb);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);

        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['name' => 'A', 'total' => '2'],
                ['name' => 'B', 'total' => '0'],
            ]);

        self::assertSame([
            ['name' => 'A', 'total' => 2],
            ['name' => 'B', 'total' => 0],
        ], $repository->getProductCountGroupedByCategory());
    }
}
