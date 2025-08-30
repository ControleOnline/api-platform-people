<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
 AS Security;
use ControleOnline\Entity\User;

class PeopleRoleService
{

  private static $mainCompany;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private DomainService          $domainService
  ) {}

  public function isFranchisee(People $people)
  {
    $mainCompany = $this->getMainCompany();

    return $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'franchisee', 1);
  }


  public function isSalesman(People $people)
  {
    $mainCompany = $this->getMainCompany();
    $isSalesman = false;

    $isSalesman = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'salesman', 1);
    if ($isSalesman) return true;

    $getPeopleCompanies = $this->manager->getRepository(PeopleLink::class)->findBy([
      'people' => $people,
      'link_type' => 'employee'
    ]);
    /**
     * @var \ControleOnline\Entity\PeopleLink $peopleCompany
     */
    foreach ($getPeopleCompanies as $peopleCompany) {
      $isSalesman = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $peopleCompany->getCompany(), 'salesman', 1);
      if ($isSalesman) return true;
    }
    return $isSalesman;
  }


  public function getAllRoles(People $people): array
  {
    $peopleRole = [];
    $mainCompany = $this->getMainCompany();

    $isSuper = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'employee', 1);
    if ($isSuper)
      $peopleRole[] = 'super';

    $family = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'family', 1);
    if ($family)
      $peopleRole[] = 'family';    

    $isFranchisee = $this->isFranchisee($people);
    if ($isFranchisee) {
      $peopleRole[] = 'franchisee';
      $peopleRole[] = 'admin';
    }

    $isClient = $this->manager->getRepository(People::class)->getCompanyPeopleLinks($mainCompany, $people, 'client', 1);
    if ($isClient)
      $peopleRole[] = 'client';


    $isSalesman = $this->isSalesman($people);
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

    if (self::$mainCompany) return self::$mainCompany;

    $peopleDomain = $this->domainService->getPeopleDomain();
    self::$mainCompany =  $peopleDomain->getPeople();

    return self::$mainCompany;
  }
}
