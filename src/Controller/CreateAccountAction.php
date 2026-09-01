<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CreateAccountAction
{
  public function __construct(
    private AccountRegistrationService $accountRegistrationService,
    private HydratorService $hydratorService,
  ) {}

  public function __invoke(Request $request)
  {
    try {
      $this->accountRegistrationService->registerFromContent(
        $request->getContent()
      );

      return new JsonResponse([
        'success' => true,
        'message' => 'Cadastro criado com sucesso. Confira seu e-mail para ativar a conta.',
      ], 202);
    } catch (\Exception $e) {
      $status = 500;
      if ($e instanceof HttpExceptionInterface) {
        $status = $e->getStatusCode();
      }

      return new JsonResponse(
        $this->hydratorService->error($e),
        $status
      );
    }
  }
}
