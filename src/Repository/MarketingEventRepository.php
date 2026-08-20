<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\MarketingEvent;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketingEvent>
 */
class MarketingEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingEvent::class);
    }

    public function findOneByIdempotencyKey(string $key): ?MarketingEvent
    {
        return $this->findOneBy(['idempotencyKey' => $key]);
    }

    /**
     * @return MarketingEvent[]
     */
    public function findByVisitorId(string $visitorId, int $limit = 100): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.visitorId = :visitorId')
            ->setParameter('visitorId', $visitorId)
            ->orderBy('e.eventAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function linkVisitorToPeople(string $visitorId, People $people): int
    {
        return $this->createQueryBuilder('e')
            ->update()
            ->set('e.people', ':people')
            ->andWhere('e.visitorId = :visitorId')
            ->andWhere('e.people IS NULL')
            ->setParameter('people', $people)
            ->setParameter('visitorId', $visitorId)
            ->getQuery()
            ->execute();
    }
}
