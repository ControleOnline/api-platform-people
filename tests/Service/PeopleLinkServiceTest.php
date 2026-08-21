<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PeopleLinkServiceTest extends TestCase
{
    public function testCanManageSalesmanClientLinkRequiresManagerAccess(): void
    {
        [$service, $salesmanClientLink] = $this->buildService(
            canAccessCallback: static fn(array $linkTypes): bool => $linkTypes === PeopleLink::MANAGER_LINK
        );

        self::assertTrue($service->canManagePeopleLink($salesmanClientLink));
        self::assertTrue($service->canViewSalesmanCommissions($salesmanClientLink));
    }

    public function testSalesmanClientLinkCanBeReadWithoutCommissionAccess(): void
    {
        [$service, $salesmanClientLink] = $this->buildService(
            canAccessCallback: static fn(array $linkTypes): bool => $linkTypes === PeopleLink::HUMAN_LINK
        );

        self::assertTrue($service->canReadPeopleLink($salesmanClientLink));
        self::assertFalse($service->canManagePeopleLink($salesmanClientLink));
        self::assertFalse($service->canViewSalesmanCommissions($salesmanClientLink));
    }

    public function testPrePersistRejectsUnauthorizedSalesmanClientWrite(): void
    {
        [$service, $salesmanClientLink] = $this->buildService(
            canAccessCallback: static fn(array $linkTypes): bool => $linkTypes === PeopleLink::HUMAN_LINK
        );

        $this->expectException(AccessDeniedException::class);

        $service->prePersist($salesmanClientLink);
    }

    private function buildService(callable $canAccessCallback): array
    {
        $currentPeople = $this->createPeople(1);
        $company = $this->createPeople(30);
        $salesman = $this->createPeople(20);
        $client = $this->createPeople(40);

        $salesmanEmploymentLink = $this->createLink($company, $salesman, 'salesman');
        $salesmanClientLink = $this->createLink($salesman, $client, 'sellers-client');

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('findBy')
            ->willReturnCallback(function (array $criteria) use ($salesman, $salesmanEmploymentLink): array {
                if (
                    ($criteria['people'] ?? null) === $salesman
                    && ($criteria['linkType'] ?? null) === 'salesman'
                ) {
                    return [$salesmanEmploymentLink];
                }

                return [];
            });

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->with(PeopleLink::class)
            ->willReturn($repository);

        $user = new class($currentPeople) {
            public function __construct(private People $people) {}

            public function getPeople(): People
            {
                return $this->people;
            }
        };

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $security = $this->createMock(TokenStorageInterface::class);
        $security->method('getToken')->willReturn($token);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService
            ->method('canAccessCompany')
            ->willReturnCallback(
                static fn(People $targetCompany, ?People $people = null, ?array $linkTypes = null): bool => $canAccessCallback($linkTypes ?? [])
            );
        $peopleRoleService
            ->method('getAccessibleCompaniesForPeople')
            ->willReturn([$company]);

        return [
            new PeopleLinkService($manager, $security, $requestStack, $peopleRoleService),
            $salesmanClientLink,
        ];
    }

    private function createPeople(int $id): People
    {
        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn($id);

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
}
