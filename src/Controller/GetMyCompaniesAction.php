<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\PeoplePackage;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Entity\PackageModules;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetMyCompaniesAction
{


  public function __construct(
    private Security $security,
    private EntityManagerInterface $em,
    private DomainService $domainService,
    private PeopleRoleService $roles,
    private FileService $fileService
  ) {}




  public function __invoke(Request $request): JsonResponse
  {
    try {
      $myCompanies = [];

      /**
       * @var \ControleOnline\Entity\User
       */
      $currentUser = $this->security->getToken()->getUser();

      /**
       * @var \ControleOnline\Entity\People
       */
      $userPeople  = $currentUser->getPeople();
      $permissions = [];


      $getPeopleCompanies = $this->roles->getDirectLinksForPeople($userPeople, PeopleLink::HUMAN_LINK);

      /**
       * @var \ControleOnline\Entity\PeopleLink $peopleCompany
       */
      foreach ($getPeopleCompanies as $peopleCompany) {
        $people = $peopleCompany->getCompany();
        if (!$people instanceof People) {
          continue;
        }

        $linkType = (string) $peopleCompany->getLinkType();
        $flagMap = [
          'employee' => 'employee_enabled',
          'owner' => 'owner_enabled',
          'director' => 'director_enabled',
          'manager' => 'manager_enabled',
          'salesman' => 'salesman_enabled',
          'after-sales' => 'after_sales_enabled',
        ];

        $configs = [];
        $domains = $this->getPeopleDomains($people);
        $packages = $this->getPeoplePackages($people);

        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      => $people->getId(),
          'visibility'  => 'public'
        ]);
        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }

        $permissions[$people->getId()] = array_values(array_unique(array_merge(
          $permissions[$people->getId()] ?? [],
          $this->roles->getCompanyPermissions($people, $userPeople)
        )));

        $existingCompany = $myCompanies[$people->getId()] ?? null;
        $userFlags = $existingCompany['user'] ?? [];
        foreach ($flagMap as $flagKey) {
          $userFlags[$flagKey] = $userFlags[$flagKey] ?? false;
        }

        if (isset($flagMap[$linkType]) && $peopleCompany->getEnabled()) {
          $userFlags[$flagMap[$linkType]] = true;
        }

        $myCompanies[$people->getId()] = [
          'id'            => $people->getId(),
          'enabled'       => $people->getEnabled(),
          'alias'         => $people->getAlias(),
          'logo'          => $this->fileService->getFileUrl($people),
          'document'      => $this->getDocument($people),
          'domains'       => $domains,
          'configs'       => $configs,
          'packages'      => $packages,
          'user'          => [
            'id' => $userPeople->getId(),
            'name' => $userPeople->getName(),
            'alias' => $userPeople->getAlias(),
            'enabled' => $userPeople->getEnabled(),
            ...$userFlags,
          ]
        ];

        if ($peopleCompany->getComission() > 0) {
          $myCompanies[$people->getId()]['commission'] = $peopleCompany->getComission();
        }
      }

      foreach ($permissions as $key => $permission) {
        $myCompanies[$key]['permission'] = array_values($permission);
      }

      usort($myCompanies, function ($a, $b) {

        if ($a['alias'] == $b['alias']) {
          return 0;
        }
        return ($a['alias'] < $b['alias']) ? -1 : 1;
      });

      return new JsonResponse([
        'response' => [
          'data'        => $myCompanies,
          'count'       => count($myCompanies),
          'error'       => '',
          'success'     => true,
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
  private function getPeoplePackages($people)
  {


    $people_packages = $this->em->getRepository(PeoplePackage::class)->findBy(['people' => $people]);
    $packages = [];
    $p_m = [];


    foreach ($people_packages as $people_package) {
      $package = $people_package->getPackage();
      $package_modules = $this->em->getRepository(PackageModules::class)->findBy(['package' => $package]);

      foreach ($package_modules as $package_module) {
        $p_m[$package_module->getId()]['users']  = $package_module->getUsers();
        $p_m[$package_module->getId()]['module'] = $package_module->getModule()->getName();
      }

      $packages[$people_package->getId()]['id']                   =  $people_package->getId();
      $packages[$people_package->getId()]['package']['id']        =  $package->getId();
      $packages[$people_package->getId()]['package']['name']      =  $package->getName();
      $packages[$people_package->getId()]['package']['active']    =  $package->isActive() ? true : false;
      $packages[$people_package->getId()]['package']['modules']   =  $p_m;
    }

    return $packages;
  }

  private function getPeopleDomains($people)
  {
    $people_domains = $this->em->getRepository(PeopleDomain::class)->findBy(['people' => $people->getId()]);
    $domains = [];

    if (!empty($people_domains)) {

      /**
       * @var PeopleDomain $company
       */
      foreach ($people_domains as $domain) {

        $domains[] = [
          'id'         => $domain->getId(),
          'domainType' => $domain->getDomainType(),
          'domain'     => $domain->getDomain()
        ];
      }
    }
    return $domains;
  }

  private function getDocument(People $company): ?string
  {
    $documents = $company->getDocument();

    /**
     * @var \ControleOnline\Entity\Document $document
     */
    $documents = $documents->filter(function ($document) {
      return $document->getDocumentType()->getDocumentType() == 'CNPJ';
    });

    return $documents->first() !== false ? $documents->first()->getDocument() : null;
  }
}
