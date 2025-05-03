<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Doctrine\ORM\QueryBuilder;
use Exception;

class PeopleService
{
  private $request;

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private RequestStack $requestStack,
  ) {
    $this->request  = $requestStack->getCurrentRequest();
  }

  public function prePersist(People $people)
  {
    $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']);
    $people->setLanguage($language);
    return $people;
  }

  public function addClient() {}


  public function discoveryClient(People $provider, People $client) {}

  public function discoveryPeople(?string $document = null, ?string  $email = null, ?array $phone = [], ?string $name = null, ?string $peopleType = null): People
  {

    if (!empty($document))
      $people = $this->getDocument($document)?->getPeople();

    if (!$people && !empty($email))
      $people = $this->getEmail($email)?->getPeople();

    if (!$people && !empty($phone))
      $people = $this->getPhone($phone['ddd'], $phone['phone'])?->getPeople();

    if (!$people) {
      $people = new People();
      $people->setName($name ?? 'Name not given');
      $people->setLanguage($this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']));
      $people->setPeopleType($peopleType ?: $this->getPeopleTypeByDocumentLen($document));
      $this->manager->persist($people);
      $this->manager->flush();
    }

    if ($document)
      $this->addDocument($people, $document);
    if ($email)
      $this->addEmail($people, $email);
    if ($phone)
      $this->addPhone($people, $phone);


    return $people;
  }

  public function addPhone(People $people, array $phone_number): Phone
  {
    $phone = $this->getPhone($phone_number['ddd'], $phone_number['phone']);
    if ($phone && $phone->getPeople()) {
      if ($phone->getPeople()->getId() != $people->getId())
        throw new Exception("Phone is in use by people " . $people->getId(), 1);
    } else {
      $phone = new Phone();
      $phone->setDdd($phone_number['ddd']);
      $phone->setPhone($phone_number['phone']);
      $phone->setPeople($people);
      $this->manager->persist($phone);
      $this->manager->flush();
    }

    return  $phone;
  }
  public function addDocument(People $people, string|int $document_number, ?string $document_type = null): Document
  {
    $document = $this->getDocument($document_number, $document_type);
    if ($document) {
      if ($document->getPeople()->getId() != $people->getId())
        throw new Exception("Document is in use by people " . $people->getId(), 1);
    } else {
      $document = new Document();
      $document->setDocument((int)$document_number);
      $document->setDocumentType($document_type ? $this->discoveryDocumentType($document_type) : $this->getDocumentTypeByDocumentLen($document_number));
      $document->setPeople($people);
      $this->manager->persist($document);
      $this->manager->flush();
    }

    return  $document;
  }

  public function addEmail(People $people, string $email_str): Email
  {
    $email = $this->getEmail($email_str);
    if ($email && $email->getPeople()) {
      if ($email->getPeople()->getId() != $people->getId())
        throw new Exception("Email is in use by people " . $people->getId(), 1);
    } else {
      $email = new Email();
      $email->setEmail($email_str);
      $email->setPeople($people);
      $this->manager->persist($email);
      $this->manager->flush();
    }

    return  $email;
  }

  public function getEmail(string $email): ?Email
  {
    return $this->manager->getRepository(Email::class)->findOneBy(['email' => $email]);
  }

  public function getPhone(string $ddd, string $phone): ?Phone
  {
    return $this->manager->getRepository(Phone::class)->findOneBy(['ddd' => $ddd, 'ddd' => $phone]);
  }


  public function discoveryDocumentType(string $document_type): DocumentType
  {
    $documentType =  $this->manager->getRepository(DocumentType::class)->findOneBy(['documentType' => $document_type]);

    if (!$documentType) {
      $documentType = new DocumentType();
      $documentType->setDocumentType($document_type);
      $this->manager->persist($documentType);
      $this->manager->flush();
    }

    return $documentType;
  }

  public function getDocument(string $document_number, ?string $document_type = null): ?Document
  {
    if (!$document_type)
      $document_type = $this->getDocumentTypeByDocumentLen($document_number);
    return $this->manager->getRepository(Document::class)->findOneBy([
      'document' => $document_number,
      'documentType' =>
      $this->discoveryDocumentType($document_type)
    ]);
  }

  public function getPeopleTypeByDocumentLen(?string $document_number = null)
  {
    return strlen($document_number) > 11 ? 'J' : 'F';
  }

  public function getDocumentTypeByDocumentLen(?string $document_number = null)
  {
    return strlen($document_number) > 11 ? 'CNPJ' : 'CPF';
  }

  public function postPersist(People $people)
  {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) return;
    $payload   = json_decode($request->getContent());
    if (isset($payload->link_type)) {
      $company = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->company));
      if ($company)
        $this->addLink($company, $people, $payload->link_type);
      else {
        $link = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->link));
        if ($payload->link_type == 'employee' && $link) {
          $this->addLink($people, $link, $payload->link_type);
          if ($payload->people_document)
            $this->addDocument($people, $payload->people_document);
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

  public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
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
    $token = $this->security->getToken();
    if (!$token) return [];
    $currentUser  =  $token->getUser();
    $companies    = [];
    if (!$currentUser) return [];

    if (!$currentUser->getPeople()->getLink()->isEmpty()) {
      foreach ($currentUser->getPeople()->getLink() as $company) {
        $companies[] = $company->getCompany();
      }
    }
    return $companies;
  }
}
