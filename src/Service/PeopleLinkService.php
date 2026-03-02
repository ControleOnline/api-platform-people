<?php

namespace ControleOnline\Service;

use ControleOnline\WhatsApp\Messages\WhatsAppMessage;
use ControleOnline\WhatsApp\Messages\WhatsAppContent;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class PeopleLinkService
{
  private $request;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security $security,
    private RequestStack $requestStack,
    private TaskService $taskService,
    private TaskInterationService $taskInterationService,
    private SalesmanService $salesmanService
  ) {
    $this->request = $requestStack->getCurrentRequest();
  }

  public function discoveryLink(People $company, People $people, $linkType): PeopleLink
  {
    $peopleLink = $this->manager->getRepository(PeopleLink::class)->findOneBy([
      'company' => $company,
      'people' => $people,
      'link_type' => $linkType
    ]);

    if (!$peopleLink)
      $peopleLink = $this->addLink($company, $people, $linkType);

    return $peopleLink;
  }

  public function addLink(People $company, People $people, $link_type): PeopleLink
  {
    $peopleLink = $this->manager->getRepository(PeopleLink::class)->findOneBy([
      'company' => $company,
      'people' => $people,
      'link_type' => $link_type
    ]);

    if (!$peopleLink)
      $peopleLink = new PeopleLink();

    $peopleLink->setCompany($company);
    $peopleLink->setPeople($people);
    $peopleLink->setLinkType($link_type);

    $this->manager->persist($peopleLink);
    $this->manager->flush();

    return $peopleLink;
  }

  public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $this->checkLink($queryBuilder, $rootAlias);
  }

  public function checkLink(QueryBuilder $queryBuilder, $rootAlias): void
  {
    $link = $this->request->query->get('link', null);
    $company = $this->request->query->get('company', null);
    $link_type = $this->request->query->get('link_type', null);

    if ($link_type) {
      $queryBuilder->join(sprintf('%s.' . ($link ? 'company' : 'link'), $rootAlias), 'PeopleLink');
      $queryBuilder->andWhere('PeopleLink.link_type IN(:link_type)');
      $queryBuilder->setParameter('link_type', $link_type);
    }

    if ($company || $link) {
      $queryBuilder->andWhere('PeopleLink.' . ($link ? 'people' : 'company') . ' IN(:people)');
      $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", ($link ?: $company)));
    }
  }

  public function getMyCompanies(): array
  {
    $token = $this->security->getToken();
    if (!$token) return [];

    $currentUser = $token->getUser();
    $companies = [];
    if (!$currentUser) return [];

    if (!$currentUser->getPeople()->getLink()->isEmpty()) {
      foreach ($currentUser->getPeople()->getLink() as $company) {
        if ($company->getLinkType() == 'employee')
          $companies[] = $company->getCompany();
      }
    }

    return $companies;
  }


  public function notifyClient(PeopleLink $peopleLink): ?PeopleLink
  {
    if ($peopleLink->getLinkType() != 'client') return null;

    $taskFor = $this->salesmanService->getSalesmanFromCompany(
      $peopleLink->getCompany(),
      $this->security->getToken()->getUser()->getPeople()
    );

    if (!$taskFor) return null;

    $task = $this->taskService->addTask(
      $peopleLink->getCompany(),
      $taskFor,
      $peopleLink->getPeople(),
      'relationship'
    );

    $messageContent = new WhatsAppContent();
    $messageContent->setBody('
    Olá,
    Sou o ' . $taskFor->getName() . ', represento a empresa ' . $peopleLink->getCompany()->getAlias() . '.
    ');


    $message = new WhatsAppMessage();
    $message->setAction('sendMessage');
    $message->setMessageContent($messageContent);


    $this->taskInterationService->addInteration(
      $this->security->getToken()->getUser()->getPeople(),
      $message,
      $task,
      'relationship',
      'public'
    );

    return  $peopleLink;
  }


  public function postPersist(PeopleLink $peopleLink): PeopleLink
  {

    $this->notifyClient($peopleLink);

    return $peopleLink;
  }
}
