<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;


class SalesmanService
{
  private $request;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security $security,
    private RequestStack $requestStack,

  ) {
    $this->request = $requestStack->getCurrentRequest();
  }

  public function getSalesmanFromCompany(People $company, People $people): ?People
  {

    $salesman = $this->manager->getRepository(PeopleLink::class)
      ->createQueryBuilder('pl')
      ->andWhere('pl.people = :company')
      ->andWhere('pl.people = :people')
      ->andWhere('pl.link_type = :type')
      ->setParameter('company', $company)
      ->setParameter('people', $people)
      ->setParameter('type', 'salesman')
      ->setMaxResults(1);


    if ($salesman->getQuery()->getOneOrNullResult())
      return $salesman->getQuery()->getOneOrNullResult()?->getPeople();



    $salesman = $this->manager->getRepository(PeopleLink::class)
      ->createQueryBuilder('pl')
      ->andWhere('pl.people = :company')
      ->andWhere('pl.link_type = :type')
      ->setParameter('company', $company)
      ->setParameter('type', 'salesman')
      ->orderBy('RAND()')
      ->setMaxResults(1);

    return $salesman->getQuery()
      ->getOneOrNullResult()?->getPeople();
  }
}
