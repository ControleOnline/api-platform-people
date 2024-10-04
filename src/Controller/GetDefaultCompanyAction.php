<?php

namespace ControleOnline\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleRoleService;

class GetDefaultCompanyAction
{
  /*
   * @var Security
   */
  private $security;
  private $em = null;
  private $roles;
  private $domain;
  private $company;

  public function __construct(
    Security $security,
    EntityManagerInterface $entityManager,
    PeopleRoleService $roles,
    private DomainService $domainService
  ) {
    $this->security = $security;
    $this->em = $entityManager;
    $this->roles = $roles;
  }

  public function __invoke(
    Request $request
  ): JsonResponse {

    try {

      $this->domain = $this->domainService->getDomain();
      $this->getCompany();

      $defaultCompany = [];
      $configs = [];
      $allConfigs = [];
      $user = $this->security->getUser();

      $permissions = $user ? $this->roles->getAllRoles($user) : ['guest'];

      if ($this->company) {
        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      =>  $this->company->getPeople()->getId(),
          'visibility'  => 'public'
        ]);

        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }

        $defaultCompany = [
          'id'         => $this->company->getPeople()->getId(),
          'alias'      => $this->company->getPeople()->getAlias(),
          'configs'    => $configs,
          'domainType' => $this->company->getDomainType(),
          'permissions' => $permissions,
          'theme'       => $this->getTheme(),
          'logo'       => $this->company->getPeople()->getImage() ? [
            'id'     => $this->company->getPeople()->getImage()->getId(),
            'domain' => $this->domainService->getMainDomain(),
            'url'    => '/files/' . $this->company->getPeople()->getImage()->getId() . '/download'
          ] : null,
        ];
      }

      return new JsonResponse([
        'response' => [
          'data'    => $defaultCompany,
          'count'   => 1,
          'error'   => '',
          'success' => true
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
  private function getCompany()
  {
    $this->company = $this->em->getRepository(PeopleDomain::class)->findOneBy(['domain' => $this->domain]);
  }

  private function getTheme()
  {
    return [
      'theme' =>  $this->company->getTheme()->getTheme(),
      'colors' =>  $this->company->getTheme()->getColors(),
      'background'  =>  $this->company->getTheme()->getBackground() ? [
        'id'     =>  $this->company->getTheme()->getBackground(),
        'domain' => $this->domainService->getMainDomain(),
        'url'    => '/files/' .  $this->company->getTheme()->getBackground() . '/download'
      ] : null,
    ];
  }
}
