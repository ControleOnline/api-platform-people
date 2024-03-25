<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
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

    public function getPeopleLink(People $people, $linkType, $maxResults = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('pl')
            ->from('ControleOnline\Entity\PeopleLink', 'pl')
            ->where('pl.people = :peopleId')
            ->andWhere('pl.link_type = :linkType')
            ->setParameter('peopleId', $people->getId())
            ->setParameter('linkType', $linkType);

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } else {
            return $queryBuilder->getQuery()->getResult();
        }
    }
}
