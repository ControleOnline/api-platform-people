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


  public function discoveryLink(People $company, People $people, $linkType): PeopleLink
  {
    $peopleLink =   $this->manager->getRepository(PeopleLink::class)->findOneBy([
      'company' => $company,
      'people' => $people,
      'linkType' => $linkType
    ]);

    if (!$peopleLink)
      $peopleLink = $this->addLink($company, $people, $linkType);

    return $peopleLink;
  }


  public function importPeopleFromCSV(
    string $company,
    string $document,
    string $segment,
    string $contact,
    array $phones,
    array $emails,
    string $address,
    string $city,
    string $uf,
    string $CEP,
    string $number,
    string $Complement,
    string $linkType,
    People $provider
  ) {

    $document = preg_replace('/\D/', '', $document);

    $companyPeople = $this->discoveryPeople(
      $document,
      $emails[0] ?? null,
      [],
      $company,
      'J'
    );

    foreach ($emails as $email) {
      $this->addEmail($companyPeople, $email);
    }

    foreach ($phones as $phone) {

      $phone = preg_replace('/\D/', '', $phone);

      if (strlen($phone) >= 10) {

        $ddd = substr($phone, 0, 2);
        $numberPhone = substr($phone, 2);

        $this->addPhone($companyPeople, [
          'ddi' => 55,
          'ddd' => $ddd,
          'phone' => $numberPhone
        ]);
      }
    }

    if ($contact) {
      $contactPeople = $this->discoveryPeople(
        null,
        null,
        [],
        $contact,
        'F'
      );
    }


    $realPeople = $companyPeople ?: $contactPeople;
    $this->discoveryLink($companyPeople, $realPeople, 'employee');
    $this->discoveryLink($provider, $realPeople, $linkType);


    return $realPeople;
  }

  public function discoveryPeople(?string $document = null, ?string  $email = null, ?array $phone = [], ?string $name = null, ?string $peopleType = null): People
  {

    // Tenta encontrar por documento
    if (!empty($document))
      $people = $this->getDocument($document)?->getPeople();

    // Se não encontrar por documento, tenta encontrar por email
    if (!$people && !empty($email))
      $people = $this->getEmail($email)?->getPeople();

    // Se não encontrar por documento ou email, tenta encontrar por telefone
    if (!$people && !empty($phone)) {

      // se tem os campos ddi, ddd e phone, tenta encontrar por telefone
      if (!empty($phone['ddi']) && !empty($phone['ddd']) && !empty($phone['phone'])) {
        // converte para inteiro para mandar par getPhone, que tem os campos como int
        $phone['ddi'] = (int)$phone['ddi'];
        $phone['ddd'] = (int)$phone['ddd'];
        $phone['phone'] = (int)$phone['phone'];
        $people = $this->getPhone($phone['ddi'], $phone['ddd'], $phone['phone'])?->getPeople();
      }
    }

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

    if (!$phone_number['ddi']) $phone_number['ddi'] = 55;

    $phone_number['ddi'] = (int)$phone_number['ddi'];
    $phone_number['ddd'] = (int)$phone_number['ddd'];
    $phone_number['phone'] = (int)$phone_number['phone'];

    $phone = $this->getPhone($phone_number['ddi'], $phone_number['ddd'], $phone_number['phone']);
    if ($phone && $phone->getPeople()) {
      if ($phone->getPeople()->getId() != $people->getId())
        throw new Exception("Phone is in use by people " . $people->getId(), 1);
    } else {
      $phone = new Phone();
      $phone->setDdi((int) $phone_number['ddi']);
      $phone->setDdd((int) $phone_number['ddd']);
      $phone->setPhone((int) $phone_number['phone']);
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
      $document_type = $document_type ? $this->discoveryDocumentType($document_type) : $this->discoveryDocumentType($this->getDocumentTypeByDocumentLen($document_number));
      $document = new Document();
      $document->setDocument((int)$document_number);
      $document->setDocumentType($document_type);
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

  public function getPhone(int $ddi, int $ddd, string $phone): ?Phone
  {
    return $this->manager->getRepository(Phone::class)->findOneBy([
      'ddi' => $ddi,
      'ddd' => $ddd,
      'phone' => $phone
    ]);
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
    if (isset($payload->linkType)) {
      $company = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->company));
      if ($company)
        $this->discoveryLink($company, $people, $payload->linkType);
      else {
        $link = $this->manager->getRepository(People::class)->find(preg_replace('/\D/', '', $payload->link));
        if ($payload->linkType == 'employee' && $link) {
          $this->discoveryLink($people, $link, $payload->linkType);
          if ($payload->people_document)
            $this->addDocument($people, $payload->people_document);
        }
      }
    }
  }

  public function addLink(People $company, People $people, $linkType): PeopleLink
  {

    $peopleLink = $this->manager->getRepository(PeopleLink::class)->findOneBy([
      'company' => $company,
      'people' => $people,
      'linkType' => $linkType
    ]);

    if (!$peopleLink)
      $peopleLink = new PeopleLink();

    $peopleLink->setCompany($company);
    $peopleLink->setPeople($people);
    $peopleLink->setLinkType($linkType);

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
    return;
    $link     = $this->request->query->get('link', null);
    $company  = $this->request->query->get('company', null);
    $linkType = $this->request->query->get('linkType', null);

    $aliases = $queryBuilder->getAllAliases();

    if (!in_array('PeopleLink', $aliases)) {
      $queryBuilder->leftJoin(
        PeopleLink::class,
        'PeopleLink',
        'WITH',
        sprintf('(PeopleLink.company = %s.id OR PeopleLink.people = %s.id)', $rootAlias, $rootAlias)
      );
    }

    if ($linkType) {
      $queryBuilder->andWhere('PeopleLink.linkType IN(:linkType)');
      $queryBuilder->setParameter('linkType', $linkType);
    }

    $peopleIds = array_filter(
      array_merge(
        !$company ? array_map(fn($c) => $c->getId(), $this->getMyCompanies()) : [],
        [
          $link ? (int) preg_replace('/\D/', '', $link) : null,
          $company ? (int) preg_replace('/\D/', '', $company) : null,
          $this->getMyPeople()?->getId()
        ]
      )
    );

    if (!empty($peopleIds)) {
      $queryBuilder->andWhere(
        $queryBuilder->expr()->orX(
          'PeopleLink.people IN(:people)',
          'PeopleLink.company IN(:people)'
        )
      );

      $queryBuilder->setParameter('people', $peopleIds);
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

  public function getMyCompanies(?array $companyType = ['prospect', 'employee', 'salesman', 'owner', 'director', 'manager']): array
  {
    $people = $this->getMyPeople();
    if (!$people) return [];
    if (!$people->getLink()->isEmpty()) {
      foreach ($people->getLink() as $company) {
        if (in_array($company->getLinkType(), $companyType))
          $companies[] = $company->getCompany();
      }
    }
    return $companies;
  }
}
