<?php

namespace ControleOnline\Tests\Doctrine;

use ControleOnline\Doctrine\PeopleActiveConstraint;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PeopleActiveConstraintTest extends TestCase
{
    public function testDoesNotReferenceDeletedWhenFieldIsUnmapped(): void
    {
        $queryBuilder = $this->queryBuilderWithFields(['enable']);

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('p.enable = true')
            ->willReturnSelf();

        PeopleActiveConstraint::apply($queryBuilder, 'p', false);
    }

    public function testUsesDeletedWhenMapped(): void
    {
        $queryBuilder = $this->queryBuilderWithFields(['deleted', 'enable']);

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('peopleLink_people_active.deleted = false')
            ->willReturnSelf();

        PeopleActiveConstraint::apply($queryBuilder, 'peopleLink_people_active', false);
    }

    public function testAllowsMissingPeopleOnLinks(): void
    {
        $queryBuilder = $this->queryBuilderWithFields(['enable']);

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('(pl_people_active.id IS NULL OR pl_people_active.enable = true)')
            ->willReturnSelf();

        PeopleActiveConstraint::apply($queryBuilder, 'pl_people_active', true);
    }

    public function testNoopsWhenNeitherFieldExists(): void
    {
        $queryBuilder = $this->queryBuilderWithFields([]);
        $queryBuilder->expects(self::never())->method('andWhere');

        PeopleActiveConstraint::apply($queryBuilder, 'p', false);
    }

    private function queryBuilderWithFields(array $fields): QueryBuilder
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->method('hasField')
            ->willReturnCallback(static fn(string $field): bool => in_array($field, $fields, true));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->with(People::class)
            ->willReturn($metadata);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getEntityManager')->willReturn($entityManager);

        return $queryBuilder;
    }
}
