<?php

namespace ControleOnline\Tests\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ControleOnline\Doctrine\Extension\PeopleSecurityExtension;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PeopleSecurityExtensionTest extends TestCase
{
    public function testAppliesSecurityFilterToPeopleCollection(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $queryBuilder = $this->queryBuilderWithoutDeletedField();
        $queryBuilder->method('getRootAliases')->willReturn(['people']);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $peopleService
            ->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, People::class, 'api_platform', 'people');

        $extension = new PeopleSecurityExtension($peopleService);
        $extension->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            People::class
        );
    }

    public function testLeavesPeopleItemPublic(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $peopleService
            ->expects(self::never())
            ->method('securityFilter');

        $extension = new PeopleSecurityExtension($peopleService);
        $extension->applyToItem(
            $queryBuilder,
            $queryNameGenerator,
            People::class,
            ['id' => 1]
        );
    }

    public function testIgnoresOtherResources(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $peopleService
            ->expects(self::never())
            ->method('securityFilter');

        $extension = new PeopleSecurityExtension($peopleService);
        $extension->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            Order::class
        );
    }

    public function testCollectionDoesNotReferenceUnmappedDeleted(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $queryBuilder = $this->queryBuilderWithoutDeletedField();
        $queryBuilder->method('getRootAliases')->willReturn(['people']);
        $queryBuilder->expects(self::never())->method('andWhere');
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $extension = new PeopleSecurityExtension($peopleService);
        $extension->applyToCollection(
            $queryBuilder,
            $queryNameGenerator,
            People::class
        );
    }

    private function queryBuilderWithoutDeletedField(): QueryBuilder
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with(People::class)->willReturn($metadata);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getEntityManager')->willReturn($entityManager);
        $queryBuilder->method('andWhere')->willReturnSelf();

        return $queryBuilder;
    }
}
