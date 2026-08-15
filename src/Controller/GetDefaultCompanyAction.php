<?php

namespace ControleOnline\Controller;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
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
      $token = $this->security->getToken();
      $user = $token ? $token->getUser() : null;
      $userPeople = is_object($user) && method_exists($user, 'getPeople')
        ? $user->getPeople()
        : null;

      $permissions = $this->company
        ? $this->roles->getCompanyPermissions($this->company, $userPeople)
        : ['guest'];

      if ($this->company) {
        $publicLogo = $this->fileService->getPeopleMediaFileUrl($this->company, 'logo');
        $publicIcon = $this->fileService->getPeopleMediaFileUrl($this->company, 'icon');
        $publicStamp = $this->fileService->getPeopleMediaFileUrl($this->company, 'stamp');
        $publicPin = $this->fileService->getPeopleMediaFileUrl($this->company, 'pin');
        $publicBackground = $this->fileService->getPeopleMediaFileUrl($this->company, 'background');

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
          'theme'       => $this->getTheme($publicBackground),
          'logo'        => $publicLogo,
          'icon'        => $publicIcon,
          'stamp'       => $publicStamp,
          'pin'         => $publicPin,
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

  private function getTheme(?array $background = null)
  {
    $theme = $this->domainService->getPeopleDomain()->getTheme();

    if (!$theme) {
      return [
        'theme' => 'DEFAULT',
        'colors' => [],
        'background'  =>  $background,
      ];
    }

    return [
      'theme' =>  $theme->getTheme(),
      'colors' =>  $theme->getColors(),
      'background'  =>  $background,
    ];
  }
}
