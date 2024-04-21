<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class PeopleService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private RequestStack $requestStack
  ) {
  }

  public function beforePersist(People $people)
  {


    $request = $this->requestStack->getCurrentRequest();

    if ($request) {
      print_r($request->request->all());
    }
    $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']);
    $people->setLanguage($language);
    return $people;
  }
}
