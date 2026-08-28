<?php

namespace ControleOnline\Doctrine;

use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies "active people" constraints without assuming People.deleted exists.
 *
 * Staging/master historically use `enable`. Dev may map `deleted` for soft-delete.
 * Referencing `deleted` when the field is not mapped yields:
 *   Class ControleOnline\Entity\People has no field or association named deleted
 */
final class PeopleActiveConstraint
{
    public static function metadata(EntityManagerInterface $entityManager): ClassMetadata
    {
        return $entityManager->getClassMetadata(People::class);
    }

    public static function hasDeletedField(EntityManagerInterface $entityManager): bool
    {
        return self::metadata($entityManager)->hasField('deleted');
    }

    public static function hasEnableField(EntityManagerInterface $entityManager): bool
    {
        return self::metadata($entityManager)->hasField('enable');
    }

    /**
     * @return string|null DQL predicate using $peopleAlias, or null when nothing to apply
     */
    public static function activePredicate(
        EntityManagerInterface $entityManager,
        string $peopleAlias,
        bool $allowMissingPeople = false
    ): ?string {
        if (self::hasDeletedField($entityManager)) {
            $pred = sprintf('%s.deleted = false', $peopleAlias);
        } elseif (self::hasEnableField($entityManager)) {
            $pred = sprintf('%s.enable = true', $peopleAlias);
        } else {
            return null;
        }

        if ($allowMissingPeople) {
            return sprintf('(%s.id IS NULL OR %s)', $peopleAlias, $pred);
        }

        return $pred;
    }

    public static function apply(
        QueryBuilder $queryBuilder,
        string $peopleAlias,
        bool $allowMissingPeople = false
    ): void {
        $pred = self::activePredicate(
            $queryBuilder->getEntityManager(),
            $peopleAlias,
            $allowMissingPeople
        );
        if ($pred === null) {
            return;
        }
        $queryBuilder->andWhere($pred);
    }
}
