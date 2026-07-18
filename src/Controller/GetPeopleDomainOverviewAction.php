<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\DatabaseSwitchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetPeopleDomainOverviewAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private DatabaseSwitchService $databaseSwitchService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $domainId = (int) preg_replace('/\D+/', '', (string) $request->attributes->get('id'));

        if ($domainId <= 0) {
            throw new NotFoundHttpException('People domain not found.');
        }

        $peopleDomain = $this->em->getRepository(PeopleDomain::class)->find($domainId);

        if (!$peopleDomain instanceof PeopleDomain) {
            throw new NotFoundHttpException('People domain not found.');
        }

        $linkedFrontDomains = $this->em->getRepository(PeopleDomain::class)->findBy(
            ['apiPeopleDomain' => $peopleDomain],
            ['domain' => 'ASC', 'id' => 'ASC']
        );

        $serverInfo = $this->databaseSwitchService->getTenantConnectionInfo(
            (string) $peopleDomain->getDomain()
        );

        $payload = [
            ...$this->normalizePeopleDomain($peopleDomain),
            'server' => $this->normalizeServerInfo($serverInfo),
            'linkedFrontDomains' => array_values(array_filter(array_map(
                fn (PeopleDomain $domain) => $this->normalizePeopleDomain($domain),
                $linkedFrontDomains
            ))),
            'testsDomain' => $this->resolveTestsDomain($peopleDomain),
        ];

        return new JsonResponse($payload);
    }

    private function normalizePeopleDomain(PeopleDomain $peopleDomain): array
    {
        return [
            'id' => $peopleDomain->getId(),
            'domain' => $peopleDomain->getDomain(),
            'domainType' => $peopleDomain->getDomainType(),
            'people' => $this->normalizePeople($peopleDomain->getPeople()),
            'theme' => $this->normalizeTheme($peopleDomain->getTheme()),
            'apiPeopleDomain' => $this->normalizePeopleDomainRelation($peopleDomain->getApiPeopleDomain()),
        ];
    }

    private function normalizePeople(?People $people): ?array
    {
        if (!$people) {
            return null;
        }

        return [
            'id' => $people->getId(),
            'alias' => $people->getAlias(),
            'name' => $people->getName(),
            'peopleType' => $people->getPeopleType(),
        ];
    }

    private function normalizeTheme(?Theme $theme): ?array
    {
        if (!$theme) {
            return null;
        }

        return [
            'id' => $theme->getId(),
            'theme' => $theme->getTheme(),
        ];
    }

    private function normalizePeopleDomainRelation(?PeopleDomain $peopleDomain): ?array
    {
        if (!$peopleDomain) {
            return null;
        }

        return [
            'id' => $peopleDomain->getId(),
            'domain' => $peopleDomain->getDomain(),
            'domainType' => $peopleDomain->getDomainType(),
        ];
    }

    private function normalizeServerInfo(array|false|null $serverInfo): ?array
    {
        if (!is_array($serverInfo) || $serverInfo === []) {
            return null;
        }

        return [
            'appHost' => $serverInfo['app_host'] ?? null,
            'dbHost' => $serverInfo['db_host'] ?? null,
            'dbName' => $serverInfo['db_name'] ?? null,
            'dbPort' => $serverInfo['db_port'] ?? null,
            'dbDriver' => $serverInfo['db_driver'] ?? null,
            'dbInstance' => $serverInfo['db_instance'] ?? null,
        ];
    }

    private function resolveTestsDomain(PeopleDomain $peopleDomain): string
    {
        $apiDomain = $peopleDomain->getApiPeopleDomain();

        if ($apiDomain instanceof PeopleDomain && trim((string) $apiDomain->getDomain()) !== '') {
            return (string) $apiDomain->getDomain();
        }

        return (string) $peopleDomain->getDomain();
    }
}
