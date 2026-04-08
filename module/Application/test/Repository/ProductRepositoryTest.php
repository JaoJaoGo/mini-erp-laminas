<?php

declare(strict_types=1);

namespace ApplicationTest\Repository;

use Application\Repository\ProductRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class ProductRepositoryTest extends TestCase
{
    public function testFindFilteredBuildsQueryWithNameAndCategory(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $repository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $qb->expects(self::once())->method('leftJoin')->with('p.categories', 'c')->willReturn($qb);
        $qb->expects(self::once())->method('addSelect')->with('c')->willReturn($qb);
        $qb->expects(self::once())->method('orderBy')->with('p.id', 'DESC')->willReturn($qb);
        $qb->expects(self::once())->method('distinct')->willReturn($qb);
        $andWhereCalls = [];
        $qb->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(static function (string $condition) use (&$andWhereCalls, $qb) {
                $andWhereCalls[] = $condition;

                return $qb;
            });

        $setParameterCalls = [];
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(static function (string $name, string $value) use (&$setParameterCalls, $qb) {
                $setParameterCalls[] = [$name, $value];

                return $qb;
            });
        $qb->expects(self::once())->method('getQuery')->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([]);

        $result = $repository->findFiltered('Teste', 'Eletrônicos');

        self::assertSame([
            'p.name LIKE :name',
            'c.name LIKE :category',
        ], $andWhereCalls);
        self::assertSame([
            ['name', '%Teste%'], ['category', '%Eletrônicos%'],
        ], $setParameterCalls);
        self::assertSame([], $result);
    }

    public function testGetActiveVsInactiveCountReturnsMappedStatus(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $repository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $qb->expects(self::once())->method('select')->with('p.isActive AS isActive, COUNT(p.id) AS total')->willReturn($qb);
        $qb->expects(self::once())->method('groupBy')->with('p.isActive')->willReturn($qb);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);

        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['isActive' => true, 'total' => '3'],
                ['isActive' => false, 'total' => '1'],
            ]);

        self::assertSame([
            'active' => 3,
            'inactive' => 1,
        ], $repository->getActiveVsInactiveCount());
    }
}
