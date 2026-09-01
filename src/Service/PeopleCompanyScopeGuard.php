<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Company-scoped access for GET/PUT /people/{id} and nested People IRIs
 * (app-community#688 / #693 / #697).
 *
 * A ROLE_HUMAN caller may only load/update a People row when:
 * - it is the same person, or
 * - PeopleRoleService::canAccessCompany allows the target (direct company
 *   link or commercial chain — POST /categories denormalizes company IRI), or
 * - the target is a company the caller belongs to (people_link.company), or
 * - caller and target share at least one people_link.company
 *   (coworkers, franchisee/filial/client linked to that company).
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

        $target = $this->em->find(People::class, $targetPeopleId);
        if ($target instanceof People && $this->roles->canAccessCompany($target, $caller)) {
            return;
        }

        if ($this->countCallerCompanyMatch($callerId, $targetPeopleId) > 0) {
            return;
        }

        if ($this->countSharedCompanyAsPeople($callerId, $targetPeopleId) > 0) {
            return;
        }

        throw new AccessDeniedException('People is outside the caller company scope.');
    }

    /**
     * Target People is the company side of a caller people_link
     * (franchisor / current company IRI denormalized on people_link write).
     */
    private function countCallerCompanyMatch(int $callerId, int $targetPeopleId): int
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(caller.id)')
            ->from(PeopleLink::class, 'caller')
            ->andWhere('IDENTITY(caller.people) = :caller')
            ->andWhere('IDENTITY(caller.company) = :target')
            ->setParameter('caller', $callerId)
            ->setParameter('target', $targetPeopleId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    /**
     * Caller and target both appear as people of the same company
     * (employee of franchisor vs franchisee PJ on people_link.people).
     */
    private function countSharedCompanyAsPeople(int $callerId, int $targetPeopleId): int
    {
        $count = $this->em->createQueryBuilder()
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

        return (int) $count;
    }
}
