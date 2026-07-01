<?php

/*
 * Contract imported from MODOS_OPERACAO.md
 * - People reads must be filtered through PeopleService::securityFilter() on collections.
 * - The public item route stays public, so the item hook is intentionally a no-op.
 */

namespace ControleOnline\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\QueryBuilder;

class PeopleSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly PeopleService $peopleService,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applySecurityFilter($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // `Get /people/{id}` is intentionally public today; keep the current contract.
    }

    private function applySecurityFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== People::class) {
            return;
        }

        $this->peopleService->securityFilter(
            $queryBuilder,
            $resourceClass,
            'api_platform',
            $queryBuilder->getRootAliases()[0] ?? null
        );
    }
}
