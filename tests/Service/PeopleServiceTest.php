<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Document;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PeopleServiceTest extends TestCase
{
    public function testGetDocumentUsesQueryBuilderInsteadOfRepository(): void
    {
        $document = $this->createMock(Document::class);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])
            ->getMock();
        $query
            ->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($document);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'innerJoin', 'andWhere', 'setParameter', 'setMaxResults', 'getQuery'])
            ->getMock();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('innerJoin')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $entityManager->expects(self::never())->method('getRepository');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn(null);

        $service = new PeopleService(
            $entityManager,
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(PeopleRoleService::class),
            $requestStack
        );

        self::assertSame($document, $service->getDocument('12345678901', 'CPF'));
    }
}
