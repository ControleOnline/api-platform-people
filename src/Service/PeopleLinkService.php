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
