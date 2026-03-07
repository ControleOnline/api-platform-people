<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;
use ControleOnline\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SalesmanService implements EventSubscriberInterface
{
  private $request;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security $security,
    private RequestStack $requestStack,

  ) {
    $this->request = $requestStack->getCurrentRequest();
  }

  public static function getSubscribedEvents(): array
  {
    return [
      EntityChangedEvent::class => 'onEntityChanged',
    ];
  }

  public function onEntityChanged(EntityChangedEvent $event)
  {
    $oldEntity = $event->getOldEntity();
    $entity = $event->getEntity();

    if (!$entity instanceof People || !$oldEntity instanceof People)
      return;

    $entity->getLink()->map(function (PeopleLink $link) {
      if ($link->getLinkType() === 'client') {
        $this->discoverSalesmanForClient($link->getCompany(), $link->getPeople());
      }
    });
  }

  public function discoverSalesmanForClient(People $company, People $client): ?People
  {

    $client->getLink()->map(function (PeopleLink $link) {
      if ($link->getLinkType() === 'salesman') {
        return $link->getCompany();
      }
    });

    $salesman = $this->getSalesmanFromCompany($company, $this->getMyPeople());
    if (!$salesman) return null;

    $peopleLink = new PeopleLink();
    $peopleLink->setCompany($salesman);
    $peopleLink->setPeople($client);
    $peopleLink->setLinkType('client');

    $this->manager->persist($peopleLink);
    $this->manager->flush();

    return $salesman;
  }

  public function getMyPeople(): ?People
  {
    $token = $this->security->getToken();
    if (!$token) return null;
    /**
     * @var \ControleOnline\Entity\User $currentUser
     */
    $currentUser  =  $token->getUser();
    if (!$currentUser) return null;
    return $currentUser->getPeople();
  }

  public function getSalesmanFromCompany(People $company, People $people): ?People
  {

    $salesman = $this->manager->getRepository(PeopleLink::class)
      ->createQueryBuilder('pl')
      ->andWhere('pl.company = :company')
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
      ->andWhere('pl.company = :company')
      ->andWhere('pl.link_type = :type')
      ->setParameter('company', $company)
      ->setParameter('type', 'salesman')
      ->orderBy('RAND()')
      ->setMaxResults(1);

    return $salesman->getQuery()
      ->getOneOrNullResult()?->getPeople();
  }
}
