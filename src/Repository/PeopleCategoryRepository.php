<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleCategory[]    findAll()
 * @method PeopleCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleCategory::class);
    }

    /**
     * @return PeopleCategory[]
     */
    public function findByPeople(People $people, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('pc')
            ->innerJoin('pc.category', 'c')
            ->addSelect('c')
            ->where('pc.people = :people')
            ->setParameter('people', $people)
            ->orderBy('pc.startDate', 'DESC');

        if ($activeOnly) {
            $qb->andWhere('pc.active = true');
        }

        return $qb->getQuery()->getResult();
    }
}
