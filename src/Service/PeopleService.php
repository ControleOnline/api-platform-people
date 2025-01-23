<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\QueryBuilder;

class PeopleService
{
  private $request;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private RequestStack $requestStack
  ) {
    $this->request  = $requestStack->getCurrentRequest();
  }

  public function beforePersist(People $people)
  {
    $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']);
    $people->setLanguage($language);
    return $people;
  }

  public function addClient() {}


  public function discoveryPeopleByDocument($document_number, $document_type, $name = null): ?People
  {

    $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $document_number]);
    if ($document)
      return $document->getPeople();


    if ($name) {

      $people = new People();
      $people->setName($name);
      $people->setLanguage($this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']));
      $people->setPeopleType($document_type == 'cpf' ? 'F' : 'J');

      $document = new Document();
      $document->setDocument($document_number);
      $document->setDocumentType($this->manager->getRepository(DocumentType::class)->findOneBy(['document_type' => $document_type]));
      $document->setPeople($people);

      $this->manager->persist($people);
      $this->manager->persist($document);

      
      $this->manager->flush();

      return $people;
    }
  }


  public function afterPersist(People $people)
  {
    $request = $this->requestStack->getCurrentRequest();
    $payload   = json_decode($request->getContent());
    if (isset($payload->link_type)) {
      $company = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->company));
      if ($company)
        $this->addLink($company, $people, $payload->link_type);
      else {
        $link = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->link));
        if ($payload->link_type == 'employee' && $link) {
          $this->addLink($people, $link, $payload->link_type);
          if ($payload->people_document) {
            $document_type = $this->manager->getRepository(DocumentType::class)->findOneBy(['document_type' => 'cnpj']);

            $document = new Document();
            $document->setPeople($people);
            $document->setDocumentType($document_type);
            $document->setDocument($payload->people_document);
            $this->manager->persist($document);
            $this->manager->flush();
          }
        }
      }
    }
  }

  public function addLink(People $company, People $people, $link_type)
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
    return  $peopleLink;
  }

  public function secutiryFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $this->checkLink($queryBuilder, $resourceClass, $applyTo, $rootAlias);
  }

  public function checkLink(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {

    $link   = $this->request->query->get('link',   null);
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
  public function checkCompany($type, QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $companies   = $this->getMyCompanies();
    $queryBuilder->andWhere(sprintf('%s.' . $type . ' IN(:companies)', $rootAlias, $rootAlias));
    $queryBuilder->setParameter('companies', $companies);

    if ($payer = $this->request->query->get('company', null)) {
      $queryBuilder->andWhere(sprintf('%s.' . $type . ' IN(:people)', $rootAlias));
      $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", $payer));
    }
  }



  public function getMyCompanies(): array
  {
    /**
     * @var \ControleOnline\Entity\User $currentUser
     */
    $currentUser  = $this->security->getUser();
    $companies    = [];
    if (!$currentUser)
      return [];

    if (!$currentUser->getPeople()->getLink()->isEmpty()) {
      foreach ($currentUser->getPeople()->getLink() as $company) {
        $companies[] = $company->getCompany();
      }
    }
    return $companies;
  }
}
