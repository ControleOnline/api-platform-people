<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\PeopleService;

class PeopleRoleService
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * People Service
   *
   * @var PeopleService
   */
  private $people  = null;

  public function __construct(
    EntityManagerInterface $entityManager,
    Security               $security,
    PeopleService          $peopleService,
    private DomainService $domainService
 
  ) {
    $this->manager  = $entityManager;
    $this->security = $security;
    $this->people   = $peopleService;
  }

  public function isFranchisee(People $people): bool
  {
    return in_array('franchisee', $this->getAllRoles($people));
  }

  public function isSuperAdmin(People $people): bool
  {
    return in_array('super', $this->getAllRoles($people));
  }

  public function isSalesman(People $people): bool
  {
    return in_array('salesman', $this->getAllRoles($people));
  }


  public function getAllRoles(People $people): array
  {
    $peopleRole = [];
    $mainCompany = $this->getMainCompany();

    $isSuper = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'employee', 1);
    if ($isSuper) 
      $peopleRole[] = 'super';

    $isFranchisee = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'franchisee', 1);
    if ($isFranchisee) {
      $peopleRole[] = 'franchisee';
      $peopleRole[] = 'admin';
    }

    $isClient = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'client', 1);
    if ($isClient) 
      $peopleRole[] = 'client';
    

    $isSalesman = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'salesman', 1);
    if ($isSalesman) 
      $peopleRole[] = 'salesman';
    

    return array_values(array_unique(empty($peopleRole) ? ['guest'] : $peopleRole));
  }

  /**
   * Retorna a people da empresa principal segundo o dominio da api
   *
   * @return People
   */
  public function getMainCompany(): People
  {
    $domain  = $this->domainService->getMainDomain();
    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      throw new \Exception(
        sprintf('Main company "%s" not found', $domain)
      );

    return $company->getPeople();
  }
}
