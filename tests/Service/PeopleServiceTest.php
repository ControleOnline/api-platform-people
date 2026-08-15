<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

    public function testResolveQueryArrayOrScalarAcceptsScalarLinkType(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['linkType' => 'client']));

        $service = new PeopleService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(PeopleRoleService::class),
            $requestStack
        );

        $reflection = new \ReflectionMethod($service, 'resolveQueryArrayOrScalar');
        $reflection->setAccessible(true);

        self::assertSame(
            'client',
            $reflection->invoke($service, $requestStack->getCurrentRequest(), 'linkType')
        );
    }

    public function testDiscoveryDocumentTypeCreatesMissingCpfWithPeopleType(): void
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])
            ->getMock();
        $query
            ->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'andWhere', 'setParameter', 'setMaxResults', 'getQuery'])
            ->getMock();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (DocumentType $documentType): bool {
                return $documentType->getDocumentType() === 'CPF'
                    && $documentType->getPeopleType() === 'F';
            }));
        $entityManager->expects(self::once())->method('flush');

        $service = new PeopleService(
            $entityManager,
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(PeopleRoleService::class),
            new RequestStack()
        );

        $documentType = $service->discoveryDocumentType('CPF');

        self::assertSame('CPF', $documentType->getDocumentType());
        self::assertSame('F', $documentType->getPeopleType());
    }

    public function testDiscoveryDocumentTypeCreatesMissingCnpjWithPeopleType(): void
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])
            ->getMock();
        $query
            ->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'andWhere', 'setParameter', 'setMaxResults', 'getQuery'])
            ->getMock();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (DocumentType $documentType): bool {
                return $documentType->getDocumentType() === 'CNPJ'
                    && $documentType->getPeopleType() === 'J';
            }));
        $entityManager->expects(self::once())->method('flush');

        $service = new PeopleService(
            $entityManager,
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(PeopleRoleService::class),
            new RequestStack()
        );

        $documentType = $service->discoveryDocumentType('CNPJ');

        self::assertSame('CNPJ', $documentType->getDocumentType());
        self::assertSame('J', $documentType->getPeopleType());
    }

    public function testCheckLinkWithoutRequestFiltersUsesVisiblePeopleIds(): void
    {
        $myPeople = $this->createPeople(7);
        $company = $this->createPeople(2);

        $tokenUser = (new User())->setPeople($myPeople);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($tokenUser);

        $security = $this->createMock(TokenStorageInterface::class);
        $security
            ->method('getToken')
            ->willReturn($token);

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService
            ->method('getAccessibleCompaniesForPeople')
            ->with($myPeople)
            ->willReturn([$company]);

        $visibilityQuery = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArrayResult'])
            ->getMock();
        $visibilityQuery
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['companyId' => 2, 'peopleId' => 7],
                ['companyId' => 2, 'peopleId' => 99],
            ]);

        $visibilityQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'setParameter', 'andWhere', 'expr', 'getQuery'])
            ->getMock();
        $visibilityQueryBuilder->method('select')->willReturnSelf();
        $visibilityQueryBuilder->method('from')->willReturnSelf();
        $visibilityQueryBuilder->method('setParameter')->willReturnSelf();
        $visibilityQueryBuilder->method('andWhere')->willReturnSelf();
        $visibilityQueryBuilder->method('expr')->willReturn(new Expr());
        $visibilityQueryBuilder->method('getQuery')->willReturn($visibilityQuery);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($visibilityQueryBuilder);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $service = new PeopleService(
            $entityManager,
            $security,
            $peopleRoleService,
            $requestStack
        );

        $rootQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['andWhere', 'setParameter', 'leftJoin'])
            ->getMock();
        $rootQueryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('resource.id IN(:peopleVisibilityIds)')
            ->willReturnSelf();
        $rootQueryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('peopleVisibilityIds', [7, 2, 99])
            ->willReturnSelf();
        $rootQueryBuilder
            ->expects(self::never())
            ->method('leftJoin');

        $service->checkLink($rootQueryBuilder, null, 'api_platform', 'resource');
    }

    private function createPeople(int $id): People
    {
        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn($id);

        return $people;
    }
}
