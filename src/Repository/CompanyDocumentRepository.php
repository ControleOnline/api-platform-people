<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\CompanyDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyDocument[]    findAll()
 * @method CompanyDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyDocument::class);
    }
}
