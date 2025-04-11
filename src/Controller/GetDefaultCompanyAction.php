<?php

namespace ControleOnline\Controller;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
 AS Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Config;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;

class GetDefaultCompanyAction
{
  private $company;

  public function __construct(
    private Security $security,
    private EntityManagerInterface $em,
    private PeopleRoleService $roles,
    private FileService $fileService,
    private domainService $domainService
  ) {

    $this->company = $this->roles->getMainCompany();
  }

  public function __invoke(): JsonResponse
  {

    try {


      $defaultCompany = [];
      $configs = [];
      $allConfigs = [];
      $user = $this->security->getToken()->getUser();

      $permissions = $user ? $this->roles->getAllRoles($this->company) : ['guest'];

      if ($this->company) {
        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      =>  $this->company->getId(),
          'visibility'  => 'public'
        ]);

        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }

        $defaultCompany = [
          'id'         => $this->company->getId(),
          'alias'      => $this->company->getAlias(),
          'configs'    => $configs,
          'domainType' => $this->domainService->getPeopleDomain()->getDomainType(),
          'permissions' => $permissions,
          'theme'       => $this->getTheme(),
          'logo'        => $this->fileService->getFileUrl($this->company)
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

  private function getTheme()
  {
    return [
      'theme' =>  $this->domainService->getPeopleDomain()->getTheme()->getTheme(),
      'colors' =>  $this->domainService->getPeopleDomain()->getTheme()->getColors(),
      'background'  =>  $this->domainService->getPeopleDomain()->getTheme()->getBackground() ? [
        'id'     =>  $this->domainService->getPeopleDomain()->getTheme()->getBackground(),
        'domain' => $this->domainService->getMainDomain(),
        'url'    => '/files/' .  $this->domainService->getPeopleDomain()->getTheme()->getBackground() . '/download'
      ] : null,
    ];
  }
}
