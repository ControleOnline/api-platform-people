<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PeopleLinkService
{
    private $request;

    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private RequestStack $requestStack,
        private PeopleRoleService $peopleRoleService,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function prePersist(PeopleLink $peopleLink): PeopleLink
    {
        $this->assertWriteAccess($peopleLink);

        return $peopleLink;
    }

    public function preUpdate(PeopleLink $peopleLink): PeopleLink
    {
        $this->assertWriteAccess($peopleLink);

        return $peopleLink;
    }

    /**
     * Write is allowed when the caller is the linked person (self / My Companies)
     * or can manage the company or the people company (owner/director/manager).
     * Cross-tenant POST without that relation is AccessDenied (IDOR).
     */
    public function canManagePeopleLink(PeopleLink $peopleLink): bool
    {
        $currentPeople = $this->getMyPeople();
        if (!$currentPeople instanceof People) {
            return false;
        }

        $currentPeopleId = (int) $currentPeople->getId();
        $linkedPeopleId = (int) ($peopleLink->getPeople()?->getId() ?? 0);
        $linkedCompanyId = (int) ($peopleLink->getCompany()?->getId() ?? 0);

        if ($linkedPeopleId !== 0 && $linkedPeopleId === $currentPeopleId) {
            return true;
        }
        if ($linkedCompanyId !== 0 && $linkedCompanyId === $currentPeopleId) {
            return true;
        }

        foreach ($this->resolveManageableCompanies($peopleLink) as $company) {
            if ($this->peopleRoleService->canAccessCompany($company, $currentPeople, PeopleLink::MANAGER_LINK)) {
                return true;
            }
        }

        return false;
    }

    private function assertWriteAccess(PeopleLink $peopleLink): void
    {
        if (!$this->canManagePeopleLink($peopleLink)) {
            throw new AccessDeniedException('You are not allowed to manage this people link.');
        }
    }

    private function resolveManageableCompanies(PeopleLink $peopleLink): array
    {
        $companies = [];
        if ($peopleLink->getCompany() instanceof People) {
            $companies[] = $peopleLink->getCompany();
        }
        if ($peopleLink->getPeople() instanceof People) {
            $companies[] = $peopleLink->getPeople();
        }

        return $companies;
    }

    private function getMyPeople(): ?People
    {
        $user = $this->security->getToken()?->getUser();

        if (!is_object($user) || !method_exists($user, 'getPeople')) {
            return null;
        }

        $people = $user->getPeople();

        return $people instanceof People ? $people : null;
    }
}
