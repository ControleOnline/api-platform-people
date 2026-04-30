<?php

namespace ControleOnline\Service {
    if (!class_exists(DomainService::class)) {
        class DomainService
        {
            public function getPeopleDomain() {}
        }
    }
}

namespace ControleOnline\Tests\Service {

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PeopleRoleServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(PeopleRoleService::class);
        $mainCompany = $reflection->getProperty('mainCompany');
        $mainCompany->setValue(null, null);
    }

    public function testGrantedRolesRequireValidCommercialChain(): void
    {
        $person = $this->createPeople(10);
        $company = $this->createPeople(20);
        $mainCompany = $this->createPeople(99);

        $service = $this->buildService([
            10 => [$this->createLink($company, $person, 'employee')],
            20 => [$this->createLink($mainCompany, $company, 'client')],
            99 => [],
        ], $mainCompany);

        self::assertSame(['ROLE_EMPLOYEE'], $service->getGrantedRoles($person));
        self::assertSame([20], $this->extractIds($service->getAccessibleCompaniesForPeople($person)));
        self::assertSame(['client', 'employee'], $this->sortValues($service->getCompanyPermissions($company, $person)));
    }

    public function testSalesmanRemainsExplicitRole(): void
    {
        $person = $this->createPeople(11);
        $company = $this->createPeople(21);
        $mainCompany = $this->createPeople(99);

        $service = $this->buildService([
            11 => [$this->createLink($company, $person, 'salesman')],
            21 => [$this->createLink($mainCompany, $company, 'client')],
            99 => [],
        ], $mainCompany);

        self::assertSame(['ROLE_SALESMAN'], $service->getGrantedRoles($person));
        self::assertFalse(in_array('ROLE_EMPLOYEE', $service->getGrantedRoles($person), true));
    }

    public function testMainCompanyOwnerGetsSuperRole(): void
    {
        $person = $this->createPeople(12);
        $mainCompany = $this->createPeople(99);

        $service = $this->buildService([
            12 => [$this->createLink($mainCompany, $person, 'owner')],
            99 => [],
        ], $mainCompany);

        self::assertSame(['ROLE_OWNER', 'ROLE_SUPER'], $this->sortValues($service->getGrantedRoles($person)));
        self::assertSame(['owner', 'super'], $this->sortValues($service->getCompanyPermissions($mainCompany, $person)));
    }

    public function testCompanyWithoutCommercialChainDoesNotGrantOperationalRole(): void
    {
        $person = $this->createPeople(13);
        $company = $this->createPeople(23);
        $mainCompany = $this->createPeople(99);

        $service = $this->buildService([
            13 => [$this->createLink($company, $person, 'owner')],
            23 => [],
            99 => [],
        ], $mainCompany);

        self::assertSame([], $service->getGrantedRoles($person));
        self::assertSame([], $service->getAccessibleCompaniesForPeople($person));
        self::assertSame(['guest'], $service->getCompanyPermissions($company, $person));
    }

    public function testDirectLinksRemainAvailableForCompanySelectorOutsideCurrentDomainChain(): void
    {
        $person = $this->createPeople(14);
        $company = $this->createPeople(24);
        $mainCompany = $this->createPeople(99);

        $service = $this->buildService([
            14 => [$this->createLink($company, $person, 'employee')],
            24 => [],
            99 => [],
        ], $mainCompany);

        self::assertCount(1, $service->getDirectLinksForPeople($person, PeopleLink::HUMAN_LINK));
        self::assertSame([], $service->getAccessibleCompaniesForPeople($person));
    }

    private function buildService(array $linksByPeopleId, People $mainCompany): PeopleRoleService
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($linksByPeopleId): array {
                $people = $criteria['people'] ?? null;

                if (!$people instanceof People) {
                    return [];
                }

                return $linksByPeopleId[(int) $people->getId()] ?? [];
            });

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->with(PeopleLink::class)
            ->willReturn($repository);

        $security = $this->createMock(TokenStorageInterface::class);

        $peopleDomain = $this->createMock(PeopleDomain::class);
        $peopleDomain->method('getPeople')->willReturn($mainCompany);

        $domainService = $this->createMock(DomainService::class);
        $domainService
            ->method('getPeopleDomain')
            ->willReturn($peopleDomain);

        return new PeopleRoleService($manager, $security, $domainService);
    }

    private function createPeople(int $id, bool $enabled = true): People
    {
        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn($id);
        $people->method('getEnabled')->willReturn($enabled);

        return $people;
    }

    private function createLink(People $company, People $people, string $linkType, bool $enabled = true): PeopleLink
    {
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($people);
        $link->setLinkType($linkType);
        $link->setEnabled($enabled);

        return $link;
    }

    private function extractIds(array $companies): array
    {
        return array_map(
            static fn(People $company): int => (int) $company->getId(),
            $companies
        );
    }

    private function sortValues(array $values): array
    {
        sort($values);

        return $values;
    }
}
}
