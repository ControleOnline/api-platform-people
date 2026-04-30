<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class PeopleRoleService
{
    private static ?People $mainCompany = null;

    private array $directLinksCache = [];
    private array $commercialChainCache = [];
    private array $grantedRolesCache = [];
    private array $accessibleCompaniesCache = [];
    private array $companyPermissionsCache = [];

    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private DomainService $domainService,
    ) {}

    public function getMainCompany(): People
    {
        if (self::$mainCompany instanceof People) {
            return self::$mainCompany;
        }

        $peopleDomain = $this->domainService->getPeopleDomain();
        self::$mainCompany = $peopleDomain->getPeople();

        return self::$mainCompany;
    }

    public function getCurrentPeople(): ?People
    {
        $user = $this->security->getToken()?->getUser();

        if (!is_object($user) || !method_exists($user, 'getPeople')) {
            return null;
        }

        $people = $user->getPeople();

        return $people instanceof People ? $people : null;
    }

    public function getGrantedRoles(?People $people = null): array
    {
        $people ??= $this->getCurrentPeople();
        if (!$people instanceof People) {
            return [];
        }

        $cacheKey = $this->buildCacheKey($people);
        if (isset($this->grantedRolesCache[$cacheKey])) {
            return $this->grantedRolesCache[$cacheKey];
        }

        $roles = [];

        foreach ($this->getActiveDirectLinks($people) as $link) {
            $company = $link->getCompany();
            if (!$company instanceof People || !$this->companyHasPanelAccess($company)) {
                continue;
            }

            $role = PeopleLink::toRole((string) $link->getLinkType());
            if ($role !== null) {
                $roles[] = $role;
            }

            if (
                $this->isMainCompany($company)
                && $link->getLinkType() === 'owner'
            ) {
                $roles[] = 'ROLE_SUPER';
            }
        }

        return $this->grantedRolesCache[$cacheKey] = array_values(array_unique($roles));
    }

    public function getAccessibleLinksForPeople(?People $people = null, ?array $linkTypes = null): array
    {
        $people ??= $this->getCurrentPeople();
        if (!$people instanceof People) {
            return [];
        }

        $allowedTypes = $this->normalizeLinkTypes($linkTypes ?? PeopleLink::HUMAN_LINK);
        $links = [];

        foreach ($this->getActiveDirectLinks($people) as $link) {
            $company = $link->getCompany();
            if (
                !$company instanceof People
                || !in_array($link->getLinkType(), $allowedTypes, true)
                || !$this->companyHasPanelAccess($company)
            ) {
                continue;
            }

            $links[] = $link;
        }

        return $links;
    }

    public function getAccessibleCompaniesForPeople(?People $people = null, ?array $linkTypes = null): array
    {
        $people ??= $this->getCurrentPeople();
        if (!$people instanceof People) {
            return [];
        }

        $cacheKey = $this->buildCacheKey($people) . ':' . implode(',', $this->normalizeLinkTypes($linkTypes ?? PeopleLink::HUMAN_LINK));
        if (isset($this->accessibleCompaniesCache[$cacheKey])) {
            return $this->accessibleCompaniesCache[$cacheKey];
        }

        $companies = [];
        foreach ($this->getAccessibleLinksForPeople($people, $linkTypes) as $link) {
            $company = $link->getCompany();
            if (!$company instanceof People) {
                continue;
            }

            $companies[$company->getId()] = $company;
        }

        return $this->accessibleCompaniesCache[$cacheKey] = array_values($companies);
    }

    public function canAccessCompany(People $company, ?People $people = null, ?array $linkTypes = null): bool
    {
        foreach ($this->getAccessibleCompaniesForPeople($people, $linkTypes) as $accessibleCompany) {
            if ((int) $accessibleCompany->getId() === (int) $company->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCompanyPermissions(People $company, ?People $people = null): array
    {
        $people ??= $this->getCurrentPeople();
        if (!$people instanceof People) {
            return ['guest'];
        }

        $cacheKey = $this->buildCacheKey($people) . ':' . (int) $company->getId();
        if (isset($this->companyPermissionsCache[$cacheKey])) {
            return $this->companyPermissionsCache[$cacheKey];
        }

        $permissions = [];

        foreach ($this->getActiveDirectLinks($people) as $link) {
            $linkedCompany = $link->getCompany();
            if (
                !$linkedCompany instanceof People
                || (int) $linkedCompany->getId() !== (int) $company->getId()
                || !$this->companyHasPanelAccess($linkedCompany)
            ) {
                continue;
            }

            $permissions[] = (string) $link->getLinkType();
            if (
                $this->isMainCompany($linkedCompany)
                && $link->getLinkType() === 'owner'
            ) {
                $permissions[] = 'super';
            }
        }

        $permissions = array_merge(
            $permissions,
            $this->resolveCommercialChainRoles($company) ?? []
        );

        $permissions = array_values(array_unique(array_filter($permissions)));

        if ($permissions === []) {
            $permissions = ['guest'];
        }

        return $this->companyPermissionsCache[$cacheKey] = $permissions;
    }

    public function getAllRoles(People $people): array
    {
        $currentPeople = $this->getCurrentPeople();

        if ($currentPeople instanceof People && (int) $currentPeople->getId() === (int) $people->getId()) {
            return $this->normalizeRoleLabels($this->getGrantedRoles($currentPeople));
        }

        return $this->getCompanyPermissions($people, $currentPeople);
    }

    public function isFranchisee(People $people): bool
    {
        return in_array('ROLE_FRANCHISEE', $this->getGrantedRoles($people), true);
    }

    public function isSalesman(People $people): bool
    {
        return in_array('ROLE_SALESMAN', $this->getGrantedRoles($people), true);
    }

    public function companyHasPanelAccess(People $company): bool
    {
        return $this->resolveCommercialChainRoles($company) !== null;
    }

    private function getActiveDirectLinks(People $people): array
    {
        $cacheKey = $this->buildCacheKey($people);
        if (isset($this->directLinksCache[$cacheKey])) {
            return $this->directLinksCache[$cacheKey];
        }

        $links = [];
        foreach ($this->manager->getRepository(PeopleLink::class)->findBy(['people' => $people]) as $link) {
            $company = $link->getCompany();
            if (
                !$company instanceof People
                || !$link->getEnabled()
                || !$company->getEnabled()
            ) {
                continue;
            }

            $links[] = $link;
        }

        return $this->directLinksCache[$cacheKey] = $links;
    }

    private function resolveCommercialChainRoles(People $company, array $visited = []): ?array
    {
        $cacheKey = (int) $company->getId();
        if ($visited === [] && array_key_exists($cacheKey, $this->commercialChainCache)) {
            return $this->commercialChainCache[$cacheKey];
        }

        if ($this->isMainCompany($company)) {
            return [];
        }

        if (isset($visited[$cacheKey])) {
            return null;
        }

        $visited[$cacheKey] = true;

        foreach ($this->manager->getRepository(PeopleLink::class)->findBy(['people' => $company]) as $link) {
            $parentCompany = $link->getCompany();
            if (
                !$parentCompany instanceof People
                || !$link->getEnabled()
                || !$parentCompany->getEnabled()
                || !in_array($link->getLinkType(), PeopleLink::PANEL_LINK, true)
            ) {
                continue;
            }

            $parentChainRoles = $this->resolveCommercialChainRoles($parentCompany, $visited);
            if ($parentChainRoles === null) {
                continue;
            }

            $resolvedRoles = array_values(array_unique(array_merge(
                [(string) $link->getLinkType()],
                $parentChainRoles
            )));

            if ($visited === [$cacheKey => true]) {
                $this->commercialChainCache[$cacheKey] = $resolvedRoles;
            }

            return $resolvedRoles;
        }

        if ($visited === [$cacheKey => true]) {
            $this->commercialChainCache[$cacheKey] = null;
        }

        return null;
    }

    private function normalizeRoleLabels(array $roles): array
    {
        return array_values(array_unique(array_map(function (string $role): string {
            return strtolower(preg_replace('/^ROLE_/', '', $role));
        }, $roles)));
    }

    private function isMainCompany(People $company): bool
    {
        return (int) $company->getId() === (int) $this->getMainCompany()->getId();
    }

    private function normalizeLinkTypes(array $linkTypes): array
    {
        return array_values(array_unique(array_map(static function (string $linkType): string {
            return trim(strtolower($linkType));
        }, $linkTypes)));
    }

    private function buildCacheKey(People $people): string
    {
        return (string) $people->getId();
    }
}
