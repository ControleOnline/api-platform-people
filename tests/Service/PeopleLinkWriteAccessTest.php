<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
        $roles->method('getGrantedRoles')->willReturn([]);

        $service = $this->makeService($caller, $roles, []);

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
        $roles->method('getGrantedRoles')->willReturn([]);

        $service = $this->makeService($caller, $roles, []);
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
        $roles->method('getGrantedRoles')->willReturn([]);

        $service = $this->makeService($caller, $roles, []);
        $this->assertSame($link, $service->prePersist($link));
    }

    /**
     * app-community#687 — company outside commercial panel chain still allows
     * owner/manager with a direct people_link to create collaborators.
     */
    public function testAllowedViaDirectHumanLinkWhenPanelChainBlocks(): void
    {
        $caller = $this->people(1);
        $company = $this->people(5);
        $collaborator = $this->people(99);
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($collaborator);
        $link->setLinkType('employee');

        $roles = $this->createMock(PeopleRoleService::class);
        // Simulates companyHasPanelAccess=false → canAccessCompany false
        $roles->method('canAccessCompany')->willReturn(false);
        $roles->method('getGrantedRoles')->willReturn([]);

        $direct = new PeopleLink();
        $direct->setCompany($company);
        $direct->setPeople($caller);
        $direct->setLinkType('owner');
        $direct->setEnabled(true);

        $service = $this->makeService($caller, $roles, [$direct]);
        $this->assertSame($link, $service->prePersist($link));
    }

    public function testAllowedForSuperUserWithoutDirectLink(): void
    {
        $caller = $this->people(1);
        $company = $this->people(5);
        $collaborator = $this->people(99);
        $link = new PeopleLink();
        $link->setCompany($company);
        $link->setPeople($collaborator);
        $link->setLinkType('employee');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('canAccessCompany')->willReturn(false);
        $roles->method('getGrantedRoles')->willReturn(['ROLE_SUPER']);

        $service = $this->makeService($caller, $roles, []);
        $this->assertSame($link, $service->prePersist($link));
    }

    /**
     * @param list<PeopleLink> $directLinks
     */
    private function makeService(People $caller, PeopleRoleService $roles, array $directLinks): PeopleLinkService
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

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturn($directLinks);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(PeopleLink::class)->willReturn($repo);

        return new PeopleLinkService(
            $em,
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
