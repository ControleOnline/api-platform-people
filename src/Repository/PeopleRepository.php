<?php

namespace ControleOnline\Repository;

use ControleOnline\Doctrine\PeopleActiveConstraint;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method People|null find($id, $lockMode = null, $lockVersion = null)
 * @method People|null findOneBy(array $criteria, array $orderBy = null)
 * @method People[]    findAll()
 * @method People[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, People::class);
    }

    public function getPeopleLinks(People $people, $linkType, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pl')
            ->from('ControleOnline\Entity\PeopleLink', 'pl')
            ->where('pl.people = :people')
            ->andWhere('pl.linkType = :linkType')
            ->setParameter('people', $people)
            ->setParameter('linkType', $linkType);

        if ($maxResults)
            $qb->setMaxResults($maxResults);


        return $qb->getQuery()->getResult();
    }


    public function getCompanyPeopleLinks(People $company,  People $people, $linkType = null, $maxResults = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('pl')
            ->from('ControleOnline\Entity\PeopleLink', 'pl')
            ->where('pl.people = :people')
            ->andWhere('pl.company = :company')
            ->setParameter('company', $company->getId())
            ->setParameter('people', $people->getId());

        if ($linkType)
            $queryBuilder->setParameter('linkType', $linkType)->andWhere('pl.linkType = :linkType');

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } else {
            return $queryBuilder->getQuery()->getResult();
        }
    }

    public function findFranchiseOwnerCandidates(People $company): array
    {
        // ALEMAC // 2026-06-16
        // Dropdown de franquia Lavego: candidatos PF com alias owner,
        // vinculados como employee nas empresas filhas da franquia informada.
        $subQueryBuilder = $this->getEntityManager()->createQueryBuilder();
        $subQueryBuilder
            ->select('IDENTITY(franchiseLink.people)')
            ->from('ControleOnline\Entity\PeopleLink', 'franchiseLink')
            ->where('franchiseLink.company = :company')
            ->andWhere('franchiseLink.linkType = :franchiseLinkType')
            ->andWhere('franchiseLink.enable = :enabled');

        $queryBuilder = $this->createQueryBuilder('people');
        $queryBuilder
            ->select('DISTINCT people')
            ->innerJoin(
                'ControleOnline\Entity\PeopleLink',
                'employeeLink',
                'WITH',
                'employeeLink.people = people.id'
            )
            ->where('people.enable = :enabled')
            ->andWhere('people.peopleType = :peopleType')
            ->andWhere('people.alias = :alias')
            ->andWhere('employeeLink.linkType = :employeeLinkType')
            ->andWhere('employeeLink.enable = :enabled')
            ->andWhere($queryBuilder->expr()->in('employeeLink.company', $subQueryBuilder->getDQL()))
            ->orderBy('people.name', 'ASC')
            ->setParameter('company', $company->getId())
            ->setParameter('franchiseLinkType', 'franchisee')
            ->setParameter('employeeLinkType', 'employee')
            ->setParameter('peopleType', 'F')
            ->setParameter('alias', 'owner')
            ->setParameter('enabled', true);

        PeopleActiveConstraint::apply($queryBuilder, 'people', false);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPublicShopFranchises(
        People $company,
        array $visibleCompanyIds = [],
        string $search = '',
        int $page = 1,
        int $itemsPerPage = 30
    ): array {
        $page = max(1, $page);
        $itemsPerPage = max(1, $itemsPerPage);
        $idRows = $this->buildPublicShopFranchisesQuery($company, $visibleCompanyIds, $search)
            ->select('DISTINCT people.id AS id')
            ->orderBy('people.alias', 'ASC')
            ->addOrderBy('people.name', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getArrayResult();
        $ids = array_values(array_filter(
            array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $idRows),
            static fn(int $id): bool => $id > 0
        ));

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('people')
            ->select('DISTINCT people')
            ->addSelect('address', 'phone', 'street', 'district', 'city', 'state', 'cep')
            ->leftJoin('people.address', 'address')
            ->leftJoin('people.phone', 'phone')
            ->leftJoin('address.street', 'street')
            ->leftJoin('street.district', 'district')
            ->leftJoin('district.city', 'city')
            ->leftJoin('city.state', 'state')
            ->leftJoin('street.cep', 'cep')
            ->where('people.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('people.alias', 'ASC')
            ->addOrderBy('people.name', 'ASC')
            ->addOrderBy('address.nickname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPublicShopFranchises(
        People $company,
        array $visibleCompanyIds = [],
        string $search = ''
    ): int {
        return (int) $this->buildPublicShopFranchisesQuery($company, $visibleCompanyIds, $search)
            ->select('COUNT(DISTINCT people.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildPublicShopFranchisesQuery(
        People $company,
        array $visibleCompanyIds = [],
        string $search = ''
    ) {
        $queryBuilder = $this->createQueryBuilder('people');
        $queryBuilder
            ->innerJoin(
                PeopleLink::class,
                'franchiseLink',
                'WITH',
                'franchiseLink.people = people.id'
            )
            ->where('franchiseLink.company = :company')
            ->andWhere('franchiseLink.linkType = :franchiseLinkType')
            ->andWhere('franchiseLink.enable = :enabled')
            ->andWhere('people.enable = :enabled')
            ->setParameter('company', $company->getId())
            ->setParameter('franchiseLinkType', 'franchisee')
            ->setParameter('enabled', true);

        $normalizedVisibleCompanyIds = array_values(array_unique(array_filter(
            array_map(static fn(mixed $value): int => (int) $value, $visibleCompanyIds),
            static fn(int $value): bool => $value > 0
        )));

        if ($normalizedVisibleCompanyIds !== []) {
            $queryBuilder
                ->andWhere('people.id IN (:visibleCompanyIds)')
                ->setParameter('visibleCompanyIds', $normalizedVisibleCompanyIds);
        }

        $normalizedSearch = trim($search);
        if ($normalizedSearch !== '') {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'LOWER(people.alias) LIKE :search',
                        'LOWER(people.name) LIKE :search'
                    )
                )
                ->setParameter('search', '%' . strtolower($normalizedSearch) . '%');
        }

        return $queryBuilder;
    }
}
