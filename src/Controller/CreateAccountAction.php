<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\Service\PeopleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ControleOnline\Service\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateAccountAction
{
  public function __construct(
    private EntityManagerInterface $manager,
    private UserService $userService,
    private PeopleService $peopleService,
    private PeopleLinkService $peopleLinkService,
    private HydratorService $hydratorService,
    private DomainService $domainService
  ) {}

  public function __invoke(Request $request)
  {
    try {

      $payload = json_decode($request->getContent());

      if (!$payload || !isset($payload->people))
        throw new BadRequestHttpException('people is required');

      $peopleData = $payload->people;

      if (
        !isset($peopleData->name) ||
        !isset($peopleData->alias) ||
        !isset($peopleData->email) ||
        !isset($peopleData->phone)
      )
        throw new BadRequestHttpException('name, alias, email and phone are required');

      if (
        !isset($peopleData->phone->ddi) ||
        !isset($peopleData->phone->ddd) ||
        !isset($peopleData->phone->phone)
      )
        throw new BadRequestHttpException('phone.ddi, phone.ddd and phone.number are required');

      $people = $this->peopleService->discoveryPeople(
        $peopleData->document ?? null,
        $peopleData->email,
        (array)$peopleData->phone,
        $peopleData->name . ' ' . $peopleData->alias,
        'F'
      );

      $company = null;

      if (isset($payload->company)) {

        $companyData = $payload->company;

        $company = $this->peopleService->discoveryPeople(
          $companyData->document ?? null,
          $companyData->email ?? null,
          $companyData->phone ?? null,
          $companyData->name,
          'J'
        );

        $this->peopleService->discoveryLink(
          $company,
          $people,
          'employee'
        );

        $client = $company;
      } else {
        $client = $people;
      }

      $mainCompany = $this->domainService->getPeopleDomain()->getPeople();

      $this->peopleService->discoveryLink(
        $mainCompany,
        $client,
        'client'
      );

      if (isset($peopleData->user)) {

        if (
          !isset($peopleData->user->user) ||
          !isset($peopleData->user->password)
        )
          throw new BadRequestHttpException('user.user and user.password are required');

        $this->userService->createUser(
          $people,
          $peopleData->user->user,
          $peopleData->user->password
        );
      }

      return new JsonResponse(
        $this->hydratorService->item(
          People::class,
          $client->getId(),
          "people:read"
        )
      );
    } catch (\Exception $e) {

      return new JsonResponse(
        $this->hydratorService->error($e),
        500
      );
    }
  }
}
