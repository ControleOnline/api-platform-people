<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
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
}
