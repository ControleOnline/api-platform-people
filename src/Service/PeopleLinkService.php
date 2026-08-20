<?php

namespace ControleOnline\Service;

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
        $this->applyVisibilityFilter($queryBuilder, $rootAlias);
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

        foreach ($this->resolveManageableCompanies($peopleLink) as $company) {
            if ($this->peopleRoleService->canAccessCompany($company, $currentPeople, PeopleLink::MANAGER_LINK)) {
                return true;
            }
        }

        return false;
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
            $linkTypes = is_array($linkType) ? $linkType : [$linkType];
            $queryBuilder->andWhere(sprintf('%s.linkType IN (:requestedLinkTypes)', $rootAlias));
            $queryBuilder->setParameter('requestedLinkTypes', $linkTypes);
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
        $currentPeople = $this->getMyPeople();
        $currentPeopleId = (int) ($currentPeople?->getId() ?? 0);
        $accessibleCompanies = $this->getMyCompanies();
        $accessibleCompanyIds = array_map(
            static fn(People $company): int => (int) $company->getId(),
            $accessibleCompanies
        );

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
