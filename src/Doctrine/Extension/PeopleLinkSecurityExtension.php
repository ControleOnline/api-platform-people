<?php

/*
 * PeopleLink collection reads must go through PeopleLinkService::securityFilter().
 * Item routes follow the same collection visibility via the filter on list/search.
 */

namespace ControleOnline\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\PeopleLinkService;
use Doctrine\ORM\QueryBuilder;

class PeopleLinkSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly PeopleLinkService $peopleLinkService,
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
        $this->applySecurityFilter($queryBuilder, $resourceClass);
    }

    private function applySecurityFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== PeopleLink::class) {
            return;
        }

        $this->peopleLinkService->securityFilter(
            $queryBuilder,
            $resourceClass,
            'api_platform',
            $queryBuilder->getRootAliases()[0] ?? null
        );
    }
}
