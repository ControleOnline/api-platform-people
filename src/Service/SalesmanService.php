<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;
use ControleOnline\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ControleOnline\WhatsApp\Messages\WhatsAppMessage;
use ControleOnline\WhatsApp\Messages\WhatsAppContent;

class SalesmanService implements EventSubscriberInterface
{

  public function __construct(
    private EntityManagerInterface $manager,
    private Security $security,
    private TaskService $taskService,
    private TaskInterationService $taskInterationService,
    private SalesmanDistributionService $salesmanDistributionService
  ) {}

  public static function getSubscribedEvents(): array
  {
    return [
      EntityChangedEvent::class => 'onEntityChanged',
    ];
  }

  public function onEntityChanged(EntityChangedEvent $event)
  {
    $entity = $event->getEntity();

    if (!$entity instanceof PeopleLink) {
      return;
    }

    // reagir apenas quando empresa vira cliente
    if ($entity->getLinkType() !== 'client') {
      return;
    }

    $company = $entity->getCompany();
    $client = $entity->getPeople();

    // evita rodar em vendedor -> cliente
    if ($this->isSalesman($company)) {
      return;
    }

    $this->discoverSalesmanForClient($company, $client);
  }

  public function discoverSalesmanForClient(People $company, People $client): ?People
  {

    // cliente já tem vendedor dessa empresa?
    if ($this->clientAlreadyHasSalesmanFromCompany($company, $client)) {
      return null;
    }

    $salesman = $this->getSalesmanFromCompany(
      $company,
      $this->getMyPeople()
    );

    if (!$salesman) {
      return null;
    }

    $salesmanLink = new PeopleLink();
    $salesmanLink->setCompany($salesman);
    $salesmanLink->setPeople($client);
    $salesmanLink->setLinkType('client');

    $this->manager->persist($salesmanLink);

    $this->notifyClient($company, $salesmanLink);

    return $salesman;
  }

  public function notifyClient(People $company, PeopleLink $salesmanLink): ?Task
  {
    if ($salesmanLink->getLinkType() !== 'client') {
      return null;
    }

    $salesman = $salesmanLink->getCompany();
    $client = $salesmanLink->getPeople();

    $task = $this->taskService->addTask(
      $company,
      $salesman,
      $client,
      'relationship'
    );

    $messageContent = new WhatsAppContent();
    $messageContent->setBody(
      "Olá,\n" .
        "Sou {$salesman->getName()}, represento a empresa {$company->getAlias()}."
    );

    $message = new WhatsAppMessage();
    $message->setAction('sendMessage');
    $message->setMessageContent($messageContent);

    $this->taskInterationService->addInteration(
      $salesman,
      $message,
      $task,
      'relationship',
      'public'
    );

    return $task;
  }

  private function clientAlreadyHasSalesmanFromCompany(People $company, People $client): bool
  {

    $result = $this->manager->getRepository(PeopleLink::class)
      ->createQueryBuilder('clientLink')
      ->join(
        PeopleLink::class,
        'salesmanLink',
        'WITH',
        'salesmanLink.people = clientLink.company AND salesmanLink.link_type = :salesman'
      )
      ->andWhere('clientLink.people = :client')
      ->andWhere('clientLink.link_type = :clientType')
      ->andWhere('salesmanLink.company = :company')
      ->setParameter('client', $client)
      ->setParameter('company', $company)
      ->setParameter('clientType', 'client')
      ->setParameter('salesman', 'salesman')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    return $result !== null;
  }

  public function getMyPeople(): ?People
  {

    $token = $this->security->getToken();

    if (!$token) {
      return null;
    }

    $user = $token->getUser();

    if (!$user || !method_exists($user, 'getPeople')) {
      return null;
    }

    return $user->getPeople();
  }

  public function getSalesmanFromCompany(People $company, ?People $me): ?People
  {

    // se eu sou vendedor da empresa, usar eu
    if ($me) {

      $result = $this->manager->getRepository(PeopleLink::class)
        ->createQueryBuilder('pl')
        ->andWhere('pl.company = :company')
        ->andWhere('pl.people = :people')
        ->andWhere('pl.link_type = :type')
        ->setParameter('company', $company)
        ->setParameter('people', $me)
        ->setParameter('type', 'salesman')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

      if ($result) {
        return $me;
      }
    }

    // usar estratégia de distribuição configurada
    return $this->salesmanDistributionService
      ->discoverSalesman($company);
  }

  private function isSalesman(People $people): bool
  {

    return (bool) $this->manager->getRepository(PeopleLink::class)
      ->createQueryBuilder('pl')
      ->andWhere('pl.people = :people')
      ->andWhere('pl.link_type = :type')
      ->setParameter('people', $people)
      ->setParameter('type', 'salesman')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }
}