<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\People;
use ControleOnline\Service\UserService;


class DeleteUserAction
{
    public function __construct(private UserService $userService) {}

    public function __invoke(People $data, Request $request): JsonResponse
    {
        try {
            $result = $this->userService->deleteUserFromContent(
                $data,
                $request->getContent()
            );

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);
        } catch (\Exception $e) {

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
}
