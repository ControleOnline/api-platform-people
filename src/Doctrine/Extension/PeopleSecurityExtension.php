<?php

/*
 * Contract imported from MODOS_OPERACAO.md
 * - People reads must be filtered through PeopleService::securityFilter() on collections.
 * - Soft-delete (app-community#374): collections default to deleted=false unless filter deleted is explicit.
 * - The public item route stays public, so the item hook is intentionally a no-op for security;
 *   soft-deleted rows remain readable by id for historical references.
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
        $this->applySoftDeleteFilter($queryBuilder, $resourceClass, $context);
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
        // Soft-deleted people remain fetchable by id for historical references.
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

    /**
     * Hide soft-deleted people from default collections unless client filters deleted explicitly.
     */
    private function applySoftDeleteFilter(QueryBuilder $queryBuilder, string $resourceClass, array $context): void
    {
        if ($resourceClass !== People::class) {
            return;
        }

        $filters = $context['filters'] ?? [];
        // Explicit filter (deleted=true|false|0|1) means client controls visibility.
        if (array_key_exists('deleted', $filters)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';
        $queryBuilder->andWhere(sprintf('%s.deleted = :peopleSoftDeletedFalse', $alias));
        $queryBuilder->setParameter('peopleSoftDeletedFalse', false);
    }
}
