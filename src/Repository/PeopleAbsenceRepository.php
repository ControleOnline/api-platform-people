<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleAbsence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleAbsence|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleAbsence|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleAbsence[]    findAll()
 * @method PeopleAbsence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleAbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleAbsence::class);
    }

    /**
     * @return PeopleAbsence[]
     */
    public function findAbsences(
        People $company,
        ?People $people,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        string $context
    ): array {
        $queryBuilder = $this->createQueryBuilder('absence');
        $queryBuilder
            ->innerJoin('absence.company', 'company')
            ->innerJoin('absence.people', 'people')
            ->where('absence.context = :context')
            ->andWhere('absence.company = :company')
            ->andWhere('absence.absenceDate BETWEEN :start AND :end')
            ->setParameter('context', $context)
            ->setParameter('company', $company)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('absence.absenceDate', 'ASC')
            ->addOrderBy('absence.id', 'ASC');

        if ($people instanceof People) {
            $queryBuilder
                ->andWhere('absence.people = :people')
                ->setParameter('people', $people);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
