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

class PeopleService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private RequestStack $requestStack
  ) {}

  public function beforePersist(People $people)
  {
    $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']);
    $people->setLanguage($language);
    return $people;
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
}
