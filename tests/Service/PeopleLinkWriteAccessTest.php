<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

final class PeopleLinkWriteAccessTest extends TestCase
{
    public function testDeniedWhenCallerHasNoRelationToCompany(): void
    {
        $caller = $this->people(1);
        $company = $this->people(50);
        $franchise = $this->people(80);
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($franchise);
        $link->setLinkType('franchisee');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturn(false);

        $service = $this->makeService($caller, $roles);

        $this->expectException(AccessDeniedException::class);
        $service->prePersist($link);
    }

    public function testAllowedWhenCallerManagesFranchisor(): void
    {
        $caller = $this->people(1);
        $company = $this->people(50);
        $franchise = $this->people(80);
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($franchise);
        $link->setLinkType('franchisee');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturnCallback(
            function (People $target) use ($company): bool {
                return (int) $target->getId() === (int) $company->getId();
            }
        );

        $service = $this->makeService($caller, $roles);
        $this->assertSame($link, $service->prePersist($link));
    }

    public function testAllowedWhenLinkingSelf(): void
    {
        $caller = $this->people(9);
        $company = $this->people(50);
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($caller);
        $link->setLinkType('owner');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturn(false);

        $service = $this->makeService($caller, $roles);
        $this->assertSame($link, $service->prePersist($link));
    }

    public function testAllowedWhenManagerWritesSellersClientLink(): void
    {
        $caller = $this->people(1);
        $seller = $this->people(40);
        $client = $this->people(70);
        $link = new PeopleLink();
        $link->setCompany($seller);
        $link->setPeople($client);
        $link->setLinkType('sellers-client');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturnCallback(
            function (People $target) use ($client): bool {
                return (int) $target->getId() === (int) $client->getId();
            }
        );

        $service = $this->makeService($caller, $roles);
        $this->assertSame($link, $service->prePersist($link));
        $this->assertSame($link, $service->preUpdate($link));
    }

    public function testDeniedWhenCallerCannotManageSellersClientLink(): void
    {
        $caller = $this->people(1);
        $seller = $this->people(40);
        $client = $this->people(70);
        $link = new PeopleLink();
        $link->setCompany($seller);
        $link->setPeople($client);
        $link->setLinkType('sellers-client');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturn(false);

        $service = $this->makeService($caller, $roles);
        $this->expectException(AccessDeniedException::class);
        $service->prePersist($link);
    }

        private function makeService(People $caller, PeopleRoleService $roles): PeopleLinkService
    {
        $user = new class($caller) implements UserInterface {
            public function __construct(private People $people) {}
            public function getPeople(): People { return $this->people; }
            public function getRoles(): array { return ['ROLE_HUMAN']; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'u'; }
        };

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return new PeopleLinkService(
            $this->createMock(EntityManagerInterface::class),
            $storage,
            new RequestStack(),
            $roles,
        );
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
