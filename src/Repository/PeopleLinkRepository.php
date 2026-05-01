<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Entity\PeopleLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method PeopleLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleLink[]    findAll()
 * @method PeopleLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleLink::class);
    }

    public function hasLinkWith(User $user, People $people): bool
    {
        $qb = $this->createQueryBuilder('pl')
            ->where('pl.company = :people')->setParameter('people', $people->getId())
            ->andWhere('pl.people = :user')->setParameter('user', $user->getPeople()->getId())
            ->andWhere('pl.link_type IN (:types)')->setParameter('types', ['employee', 'family']);

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }
}
