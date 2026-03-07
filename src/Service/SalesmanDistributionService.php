<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Doctrine\ORM\EntityManagerInterface;

class SalesmanDistributionService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ConfigService $configService
    ) {}

    public function discoverSalesman(People $company): ?People
    {
        $strategy = $this->configService->getConfig(
            $company,
            'salesman-distribution-strategy'
        ) ?? 'random';

        return match ($strategy) {
            'round_robin' => $this->roundRobin($company),
            'least_clients' => $this->leastClients($company),
            'last_received' => $this->lastReceived($company),
            default => $this->random($company),
        };
    }

    private function getSalesmen(People $company)
    {
        return $this->manager->getRepository(PeopleLink::class)
            ->findBy([
                'company' => $company,
                'linkType' => 'salesman'
            ]);
    }

    private function random(People $company): ?People
    {
        $salesmen = $this->getSalesmen($company);

        if (!$salesmen)
            return null;

        $link = $salesmen[array_rand($salesmen)];

        return $link->getPeople();
    }

    private function roundRobin(People $company): ?People
    {
        $salesmen = $this->getSalesmen($company);

        if (!$salesmen)
            return null;

        $last = $this->lastReceived($company);

        if (!$last)
            return $salesmen[0]->getPeople();

        foreach ($salesmen as $index => $link) {

            if ($link->getPeople()->getId() === $last->getId()) {

                $next = $salesmen[$index + 1] ?? $salesmen[0];

                return $next->getPeople();
            }
        }

        return $salesmen[0]->getPeople();
    }

    private function lastReceived(People $company): ?People
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('pl')
            ->from(PeopleLink::class, 'pl')
            ->join(
                PeopleLink::class,
                'sl',
                'WITH',
                'sl.people = pl.company AND sl.linkType = :salesman'
            )
            ->where('pl.linkType = :client')
            ->andWhere('sl.company = :company')
            ->setParameter('salesman', 'salesman')
            ->setParameter('client', 'client')
            ->setParameter('company', $company)
            ->orderBy('pl.id', 'DESC')
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result->getCompany() : null;
    }

    private function leastClients(People $company): ?People
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('pl.company as salesman, COUNT(pl.id) as total')
            ->from(PeopleLink::class, 'pl')
            ->join(
                PeopleLink::class,
                'sl',
                'WITH',
                'sl.people = pl.company AND sl.linkType = :salesman'
            )
            ->where('pl.linkType = :client')
            ->andWhere('sl.company = :company')
            ->setParameter('salesman', 'salesman')
            ->setParameter('client', 'client')
            ->setParameter('company', $company)
            ->groupBy('pl.company')
            ->orderBy('total', 'ASC')
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result['salesman'] : null;
    }
}