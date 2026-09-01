<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleCompanyScopeGuard;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class PeopleCompanyScopeGuardTest extends TestCase
{
    public function testDeniesAnonymousCaller(): void
    {
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getCurrentPeople')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);

        $guard = new PeopleCompanyScopeGuard($em, $roles);

        $this->expectException(AccessDeniedException::class);
        $guard->assertAccessible(105790);
    }

    public function testAllowsSelf(): void
    {
        $caller = $this->people(105790);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getCurrentPeople')->willReturn($caller);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('createQueryBuilder');

        $guard = new PeopleCompanyScopeGuard($em, $roles);
        $guard->assertAccessible(105790);
        $this->addToAssertionCount(1);
    }

    public function testDeniesWhenNoCompanyRelation(): void
    {
        $caller = $this->people(1);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getCurrentPeople')->willReturn($caller);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $this->queryBuilderReturning(0),
            $this->queryBuilderReturning(0),
        );

        $guard = new PeopleCompanyScopeGuard($em, $roles);

        $this->expectException(AccessDeniedException::class);
        $guard->assertAccessible(105790);
    }

    public function testAllowsWhenTargetIsCallerCompany(): void
    {
        $caller = $this->people(1);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getCurrentPeople')->willReturn($caller);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('createQueryBuilder')->willReturn(
            $this->queryBuilderReturning(1),
        );

        $guard = new PeopleCompanyScopeGuard($em, $roles);
        $guard->assertAccessible(5);
        $this->addToAssertionCount(1);
    }

    public function testAllowsWhenSharedCompanyExists(): void
    {
        $caller = $this->people(1);
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getCurrentPeople')->willReturn($caller);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $this->queryBuilderReturning(0),
            $this->queryBuilderReturning(1),
        );

        $guard = new PeopleCompanyScopeGuard($em, $roles);
        $guard->assertAccessible(105790);
        $this->addToAssertionCount(1);
    }

    private function queryBuilderReturning(int $count): QueryBuilder
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn($count);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    private function people(int $id): People
    {
        $people = new People();
        $ref = new \ReflectionProperty(People::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($people, $id);

        return $people;
    }
}
