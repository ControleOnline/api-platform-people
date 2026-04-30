<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CreateAccountAction
{
  public function __construct(
    private AccountRegistrationService $accountRegistrationService,
    private HydratorService $hydratorService,
  ) {}

  public function __invoke(Request $request)
  {
    try {
      $client = $this->accountRegistrationService->registerFromContent(
        $request->getContent()
      );

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
