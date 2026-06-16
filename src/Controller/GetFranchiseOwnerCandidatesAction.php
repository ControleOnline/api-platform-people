<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class GetFranchiseOwnerCandidatesAction
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private PeopleRoleService $roles,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // ALEMAC // 2026-06-16
            // Endpoint dedicado ao dropdown de proprietarios candidatos
            // para cadastro de franquia no fluxo Lavego.
            $companyId = (int) preg_replace('/\D/', '', (string) $request->query->get('companyId', ''));
            if ($companyId <= 0) {
                throw new \InvalidArgumentException('companyId is required.');
            }

            $company = $this->em->getRepository(People::class)->find($companyId);
            if (!$company instanceof People) {
                throw new \RuntimeException('Company not found.');
            }

            $user = $this->security->getToken()?->getUser();
            $currentPeople = is_object($user) && method_exists($user, 'getPeople')
                ? $user->getPeople()
                : null;

            if (!$currentPeople instanceof People || !$this->roles->canAccessCompany($company, $currentPeople)) {
                throw new \RuntimeException('Access denied.');
            }

            $items = $this->em
                ->getRepository(People::class)
                ->findFranchiseOwnerCandidates($company);

            $data = array_map(
                static fn(People $people): array => [
                    'id' => $people->getId(),
                    '@id' => '/people/' . $people->getId(),
                    'name' => $people->getName(),
                    'alias' => $people->getAlias(),
                    'peopleType' => $people->getPeopleType(),
                    'enable' => $people->getEnabled(),
                ],
                $items,
            );

            return new JsonResponse([
                'response' => [
                    'data' => $data,
                    'count' => count($data),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ], $e instanceof \InvalidArgumentException ? 400 : 403);
        }
    }
}
