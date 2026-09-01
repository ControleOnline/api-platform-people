<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Company-scoped access for GET/PUT /people/{id} (app-community#688).
 *
 * A ROLE_HUMAN caller may only load/update a People row when:
 * - it is the same person, or
 * - caller and target share at least one people_link.company.
 *
 * Does not disable Doctrine filters. Does not fall back to unscoped SQL.
 */
final class PeopleCompanyScopeGuard
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PeopleRoleService $roles,
    ) {
    }

    public function assertAccessible(int $targetPeopleId): void
    {
        if ($targetPeopleId <= 0) {
            throw new AccessDeniedException('People id is required.');
        }

        $caller = $this->roles->getCurrentPeople();
        if (!$caller instanceof People) {
            throw new AccessDeniedException('Authentication required to access people.');
        }

        $callerId = (int) $caller->getId();
        if ($callerId === $targetPeopleId) {
            return;
        }

        $shared = $this->em->createQueryBuilder()
            ->select('COUNT(target.id)')
            ->from(PeopleLink::class, 'target')
            ->innerJoin(
                PeopleLink::class,
                'caller',
                'WITH',
                'caller.company = target.company'
            )
            ->andWhere('IDENTITY(target.people) = :target')
            ->andWhere('IDENTITY(caller.people) = :caller')
            ->setParameter('target', $targetPeopleId)
            ->setParameter('caller', $callerId)
            ->getQuery()
            ->getSingleScalarResult();

        if ((int) $shared > 0) {
            return;
        }

        throw new AccessDeniedException('People is outside the caller company scope.');
    }
}
