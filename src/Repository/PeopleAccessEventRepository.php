<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleAccessEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleAccessEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleAccessEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleAccessEvent[]    findAll()
 * @method PeopleAccessEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleAccessEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleAccessEvent::class);
    }

    /**
     * @return PeopleAccessEvent[]
     */
    public function findTimesheetEvents(
        People $company,
        ?People $people,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        string $context
    ): array {
        $queryBuilder = $this->createQueryBuilder('event');
        $queryBuilder
            ->innerJoin('event.company', 'company')
            ->innerJoin('event.people', 'people')
            ->where('event.context = :context')
            ->andWhere('event.company = :company')
            ->andWhere('event.eventAt BETWEEN :start AND :end')
            ->setParameter('context', $context)
            ->setParameter('company', $company)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('event.eventAt', 'ASC')
            ->addOrderBy('event.id', 'ASC');

        if ($people instanceof People) {
            $queryBuilder
                ->andWhere('event.people = :people')
                ->setParameter('people', $people);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
