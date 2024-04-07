<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class PeopleService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,

  ) {
  }

  public function beforePersist(People $people)
  {
    $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-br']);
    $people->setLanguage($language);
  }
}
