<?php

namespace ControleOnline\Service;

use ControleOnline\Doctrine\PeopleActiveConstraint;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PeopleLinkService
{
    private const LINK_TYPE_SALESMAN = 'salesman';
    private const LINK_TYPE_SELLERS_CLIENT = 'sellers-client';

    private array $salesmanCompaniesCache = [];

    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private RequestStack $requestStack,
        private PeopleRoleService $peopleRoleService,
    ) {}

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $rootAlias ??= $queryBuilder->getRootAliases()[0] ?? 'peopleLink';

        $this->applyRequestedFilters($queryBuilder, $rootAlias);
        $this->applyActivePeopleFilter($queryBuilder, $rootAlias);
        $this->applyVisibilityFilter($queryBuilder, $rootAlias);
    }

    /**
     * Hide people_links whose linked people is soft-deleted (operational removal).
     * Employee/collaborator listings load via people_links; without this filter
     * soft-deleted PF would still appear after DELETE /people/{id}.
     */
    private function applyActivePeopleFilter(QueryBuilder $queryBuilder, string $rootAlias): void
    {
        $peopleAlias = $rootAlias . '_people_active';
        // Left join keeps links even if people relation is sparse.
        if (!in_array($peopleAlias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin(sprintf('%s.people', $rootAlias), $peopleAlias);
        }
        // Use mapped field only: People.deleted is not present on master/staging.
        PeopleActiveConstraint::apply($queryBuilder, $peopleAlias, true);
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

    public function preRemove(PeopleLink $peopleLink): PeopleLink
    {
        $this->assertWriteAccess($peopleLink);

        return $peopleLink;
    }

    public function canReadPeopleLink(PeopleLink $peopleLink): bool
    {
        $currentPeople = $this->getMyPeople();
        if (!$currentPeople instanceof People) {
            return false;
        }

        if ($this->isSuperUserForPeople($currentPeople)) {
            return true;
        }

        $currentPeopleId = (int) $currentPeople->getId();
        $linkedPeopleId = (int) ($peopleLink->getPeople()?->getId() ?? 0);
        $linkedCompanyId = (int) ($peopleLink->getCompany()?->getId() ?? 0);

        if (
            $linkedPeopleId !== 0
            && ($linkedPeopleId === $currentPeopleId || $linkedCompanyId === $currentPeopleId)
        ) {
            return true;
        }

        foreach ($this->resolveReadableCompanies($peopleLink) as $company) {
            if ($this->hasDirectLinkToCompany($company, $currentPeople, PeopleLink::HUMAN_LINK)) {
                return true;
            }
            if ($this->peopleRoleService->canAccessCompany($company, $currentPeople, PeopleLink::HUMAN_LINK)) {
                return true;
            }
        }

        return false;
    }

    public function canManagePeopleLink(PeopleLink $peopleLink): bool
    {
        $currentPeople = $this->getMyPeople();
        if (!$currentPeople instanceof People) {
            return false;
        }

        if ($this->isSuperUserForPeople($currentPeople)) {
            return true;
        }

        foreach ($this->resolveManageableCompanies($peopleLink) as $company) {
            if ($this->hasDirectLinkToCompany($company, $currentPeople, PeopleLink::MANAGER_LINK)) {
                return true;
            }
            if ($this->peopleRoleService->canAccessCompany($company, $currentPeople, PeopleLink::MANAGER_LINK)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Direct people_link to company, ignoring commercial panel chain filters.
     *
     * @param list<string> $linkTypes
     */
    private function hasDirectLinkToCompany(People $company, People $currentPeople, array $linkTypes): bool
    {
        $allowed = array_map(
            static fn (string $type): string => strtolower(trim($type)),
            $linkTypes
        );

        foreach ($this->manager->getRepository(PeopleLink::class)->findBy([
            'people' => $currentPeople,
            'company' => $company,
        ]) as $link) {
            if (!$link instanceof PeopleLink || !$link->getEnabled()) {
                continue;
            }

            $type = strtolower(trim((string) $link->getLinkType()));
            if ($type !== '' && in_array($type, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    private function isSuperUserForPeople(People $currentPeople): bool
    {
        $roles = $this->peopleRoleService->getGrantedRoles($currentPeople);

        return in_array('ROLE_SUPER', $roles, true)
            || in_array('super', $roles, true);
    }

    public function canViewSalesmanCommissions(PeopleLink $peopleLink): bool
    {
        if (!$this->isSalesmanClientLink($peopleLink)) {
            return true;
        }

        return $this->canManagePeopleLink($peopleLink);
    }

    private function assertWriteAccess(PeopleLink $peopleLink): void
    {
        $canWrite = $this->isSalesmanClientLink($peopleLink)
            ? $this->canManagePeopleLink($peopleLink)
            : $this->canReadPeopleLink($peopleLink);

        if (!$canWrite) {
            throw new AccessDeniedException('You are not allowed to manage this people link.');
        }
    }

    private function applyRequestedFilters(QueryBuilder $queryBuilder, string $rootAlias): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return;
        }

        $this->applyScalarFilter($queryBuilder, $rootAlias, 'id', $request->query->get('id'));
        $this->applyRelationFilter($queryBuilder, $rootAlias, 'people', $request->query->get('people'));
        $this->applyRelationFilter($queryBuilder, $rootAlias, 'company', $request->query->get('company'));

        $linkType = $request->query->all('linkType');
        if ($linkType === []) {
            $linkType = $request->query->get('linkType', null);
        }

        if ($linkType) {
            $linkTypes = is_array($linkType) ? array_values($linkType) : [$linkType];
            // people_link.link_type is MySQL SET — FIND_IN_SET is the reliable predicate.
            $ors = [];
            foreach ($linkTypes as $i => $lt) {
                $param = 'requestedLinkType' . $i;
                $ors[] = sprintf('FIND_IN_SET(:%s, %s.linkType) > 0', $param, $rootAlias);
                $queryBuilder->setParameter($param, (string) $lt);
            }
            if ($ors !== []) {
                $queryBuilder->andWhere($queryBuilder->expr()->orX(...$ors));
            }
        }

        if ($request->query->has('enable')) {
            $queryBuilder->andWhere(sprintf('%s.enable = :requestedEnabled', $rootAlias));
            $queryBuilder->setParameter(
                'requestedEnabled',
                filter_var($request->query->get('enable'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            );
        }
    }

    private function applyVisibilityFilter(QueryBuilder $queryBuilder, string $rootAlias): void
    {
        // ROLE_SUPER (owner of main company): full collection visibility.
        // Needed so My Company Details can list franchisee links of any company_id.
        if ($this->isSuperUser()) {
            return;
        }

        $currentPeople = $this->getMyPeople();
        $currentPeopleId = (int) ($currentPeople?->getId() ?? 0);
        $accessibleCompanies = $this->getMyCompanies();
        $accessibleCompanyIds = array_map(
            static fn(People $company): int => (int) $company->getId(),
            $accessibleCompanies
        );

        // Explicit company= filter: expand via commercial chain (franqueadora → franquia)
        // so managers of the parent can list franchisee people_links of that company.
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request && $request->query->has('company')) {
            $requestedCompanyId = (int) $this->normalizeIdentifier($request->query->get('company'));
            if (
                $requestedCompanyId > 0
                && !in_array($requestedCompanyId, $accessibleCompanyIds, true)
                && $currentPeople instanceof People
            ) {
                $companyRef = $this->manager->getReference(People::class, $requestedCompanyId);
                // canAccessCompany depends on companyHasPanelAccess (commercial chain).
                // Owners of companies outside that chain still need to list Contatos
                // (app-community#687 — people_links only returned self/owner link).
                if (
                    $this->hasDirectLinkToCompany($companyRef, $currentPeople, PeopleLink::HUMAN_LINK)
                    || $this->peopleRoleService->canAccessCompany($companyRef, $currentPeople, PeopleLink::HUMAN_LINK)
                ) {
                    $accessibleCompanyIds[] = $requestedCompanyId;
                }
            }
        }

        if ($currentPeopleId === 0 && $accessibleCompanyIds === []) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $salesmanCompanyAlias = $this->ensureSalesmanCompanyJoin($queryBuilder, $rootAlias);
        $clientCompanyAlias = $this->ensureClientCompanyJoin($queryBuilder, $rootAlias);

        $visibilityConditions = [];

        if ($currentPeopleId !== 0) {
            $visibilityConditions[] = sprintf('%s.people = :currentPeopleId', $rootAlias);
            $visibilityConditions[] = sprintf('%s.company = :currentPeopleId', $rootAlias);
            $queryBuilder->setParameter('currentPeopleId', $currentPeopleId);
        }

        if ($accessibleCompanyIds !== []) {
            $visibilityConditions[] = sprintf('%s.company IN (:accessibleCompanies)', $rootAlias);
            $visibilityConditions[] = sprintf('%s.people IN (:accessibleCompanies)', $rootAlias);
            $visibilityConditions[] = sprintf('%s.company IN (:accessibleCompanies)', $salesmanCompanyAlias);
            $visibilityConditions[] = sprintf('%s.company IN (:accessibleCompanies)', $clientCompanyAlias);
            $queryBuilder->setParameter('accessibleCompanies', $accessibleCompanyIds);
        }

        if ($visibilityConditions === []) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$visibilityConditions));
    }

    private function isSuperUser(): bool
    {
        return in_array(
            'ROLE_SUPER',
            $this->peopleRoleService->getGrantedRoles($this->getMyPeople()),
            true
        );
    }

    private function applyScalarFilter(QueryBuilder $queryBuilder, string $rootAlias, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parameter = 'requested_' . $field;
        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $rootAlias, $field, $parameter));
        $queryBuilder->setParameter($parameter, $this->normalizeIdentifier($value));
    }

    private function applyRelationFilter(QueryBuilder $queryBuilder, string $rootAlias, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parameter = 'requested_' . $field;
        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $rootAlias, $field, $parameter));
        $queryBuilder->setParameter($parameter, $this->normalizeIdentifier($value));
    }

    private function ensureSalesmanCompanyJoin(QueryBuilder $queryBuilder, string $rootAlias): string
    {
        $alias = 'salesmanCompanyLink';
        if (!in_array($alias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin(
                PeopleLink::class,
                $alias,
                'WITH',
                sprintf(
                    '%s.people = %s.company AND %s.linkType = :salesmanLinkType',
                    $alias,
                    $rootAlias,
                    $alias
                )
            );
            $queryBuilder->setParameter('salesmanLinkType', self::LINK_TYPE_SALESMAN);
        }

        return $alias;
    }

    private function ensureClientCompanyJoin(QueryBuilder $queryBuilder, string $rootAlias): string
    {
        $alias = 'clientCompanyLink';
        if (!in_array($alias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin(
                PeopleLink::class,
                $alias,
                'WITH',
                sprintf(
                    '%s.people = %s.people AND %s.linkType = :clientLinkType',
                    $alias,
                    $rootAlias,
                    $alias
                )
            );
            $queryBuilder->setParameter('clientLinkType', 'client');
        }

        return $alias;
    }

    private function resolveReadableCompanies(PeopleLink $peopleLink): array
    {
        if ($this->isSalesmanClientLink($peopleLink)) {
            return $this->getSalesmanCompanies($peopleLink->getCompany());
        }

        $companies = [];
        if ($peopleLink->getCompany() instanceof People) {
            $companies[] = $peopleLink->getCompany();
        }

        if ($peopleLink->getPeople() instanceof People) {
            $companies[] = $peopleLink->getPeople();
        }

        return $this->uniqueCompanies($companies);
    }

    private function resolveManageableCompanies(PeopleLink $peopleLink): array
    {
        if ($this->isSalesmanClientLink($peopleLink)) {
            return $this->getSalesmanCompanies($peopleLink->getCompany());
        }

        return $this->resolveReadableCompanies($peopleLink);
    }

    private function getSalesmanCompanies(?People $salesman): array
    {
        $salesmanId = (int) ($salesman?->getId() ?? 0);
        if ($salesmanId === 0) {
            return [];
        }

        if (isset($this->salesmanCompaniesCache[$salesmanId])) {
            return $this->salesmanCompaniesCache[$salesmanId];
        }

        $links = $this->manager->getRepository(PeopleLink::class)->findBy([
            'people' => $salesman,
            'linkType' => self::LINK_TYPE_SALESMAN,
        ]);

        $companies = [];
        foreach ($links as $link) {
            if (!$link instanceof PeopleLink || !$link->getEnabled()) {
                continue;
            }

            $company = $link->getCompany();
            if ($company instanceof People) {
                $companies[] = $company;
            }
        }

        return $this->salesmanCompaniesCache[$salesmanId] = $this->uniqueCompanies($companies);
    }

    private function uniqueCompanies(array $companies): array
    {
        $indexedCompanies = [];

        foreach ($companies as $company) {
            if (!$company instanceof People) {
                continue;
            }

            $companyId = (int) $company->getId();
            if ($companyId === 0) {
                continue;
            }

            $indexedCompanies[$companyId] = $company;
        }

        return array_values($indexedCompanies);
    }

    private function normalizeIdentifier(mixed $value): mixed
    {
        if ($value instanceof People) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $normalized = preg_replace('/\D/', '', (string) $value);

        return $normalized === '' ? $value : (int) $normalized;
    }

    private function isSalesmanClientLink(PeopleLink $peopleLink): bool
    {
        return trim(strtolower((string) $peopleLink->getLinkType())) === self::LINK_TYPE_SELLERS_CLIENT;
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

    private function getMyCompanies(?array $linkTypes = PeopleLink::EMPLOYEE_LINK): array
    {
        return $this->peopleRoleService->getAccessibleCompaniesForPeople(
            $this->getMyPeople(),
            $linkTypes
        );
    }
}
