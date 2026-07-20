<?php

namespace ControleOnline\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PhoneService
{
    public function __construct(
        private PeopleService $peopleService,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function securityFilter(
        QueryBuilder $queryBuilder,
        $resourceClass = null,
        $applyTo = null,
        $rootAlias = null
    ): void {
        if ($this->authorizationChecker->isGranted('ROLE_HUMAN')) {
            return;
        }

        $people = $this->peopleService->getMyPeople();
        if (!$people || !$rootAlias) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $queryBuilder->andWhere(sprintf('%s.people = :phoneSecurityPeople', $rootAlias));
        $queryBuilder->setParameter('phoneSecurityPeople', $people);
    }
}
