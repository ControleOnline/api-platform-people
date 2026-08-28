<?php

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
        // Get /people/{id} remains public; soft-deleted rows stay readable by id.
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

    private function applySoftDeleteFilter(QueryBuilder $queryBuilder, string $resourceClass, array $context): void
    {
        if ($resourceClass !== People::class) {
            return;
        }

        $filters = $context['filters'] ?? [];
        if (array_key_exists('deleted', $filters)) {
            return;
        }

        try {
            $em = $queryBuilder->getEntityManager();
            if (!$em->getClassMetadata(People::class)->hasField('deleted')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';
        $queryBuilder->andWhere(sprintf('%s.deleted = :peopleSoftDeletedFalse', $alias));
        $queryBuilder->setParameter('peopleSoftDeletedFalse', false);
    }
}
