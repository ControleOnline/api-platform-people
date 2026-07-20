<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\EmailService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\PhoneService;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ContactSecurityServiceTest extends TestCase
{
    public static function services(): array
    {
        return [
            [EmailService::class, 'contact.people = :emailSecurityPeople', 'emailSecurityPeople'],
            [PhoneService::class, 'contact.people = :phoneSecurityPeople', 'phoneSecurityPeople'],
        ];
    }

    #[DataProvider('services')]
    public function testClientReadsAreRestrictedToAuthenticatedPeople(
        string $serviceClass,
        string $expectedWhere,
        string $expectedParameter
    ): void {
        $people = $this->createStub(People::class);
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->expects(self::once())->method('getMyPeople')->willReturn($people);

        $authorization = $this->createMock(AuthorizationCheckerInterface::class);
        $authorization->expects(self::once())
            ->method('isGranted')
            ->with('ROLE_HUMAN')
            ->willReturn(false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())->method('andWhere')->with($expectedWhere);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with($expectedParameter, $people);

        $service = new $serviceClass($peopleService, $authorization);
        $service->securityFilter($queryBuilder, null, 'collection', 'contact');
    }

    #[DataProvider('services')]
    public function testHumanReadsKeepExistingScope(
        string $serviceClass,
        string $expectedWhere,
        string $expectedParameter
    ): void {
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->expects(self::never())->method('getMyPeople');

        $authorization = $this->createMock(AuthorizationCheckerInterface::class);
        $authorization->method('isGranted')->with('ROLE_HUMAN')->willReturn(true);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::never())->method('andWhere');
        $queryBuilder->expects(self::never())->method('setParameter');

        $service = new $serviceClass($peopleService, $authorization);
        $service->securityFilter($queryBuilder, null, 'collection', 'contact');
    }
}
