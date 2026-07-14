<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\EmployeeProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmployeeProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeProfile[]    findAll()
 * @method EmployeeProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeProfile::class);
    }
}
