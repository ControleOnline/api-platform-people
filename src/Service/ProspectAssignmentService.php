<?php
// fluxo: vendedor-cadastro | etapa: prospect-assignment

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gives an imported/registered prospect (or client) a seller of the company:
 * - uploaded by a seller of the company -> it goes to his own portfolio;
 * - uploaded by the company -> the configured distribution strategy
 *   (salesman-distribution-strategy) picks the seller.
 * The seller gets a "sellers-client" link and, unless the company config
 * crm-import-creates-opportunity is disabled, an open "relationship" task
 * (opportunity) in his CRM.
 */
class ProspectAssignmentService
{
    public const OPPORTUNITY_CONFIG = 'crm-import-creates-opportunity';
    private const ASSIGNABLE_LINK_TYPES = ['client', 'prospect'];

    public function __construct(
        private EntityManagerInterface $manager,
        private PeopleService $peopleService,
        private SalesmanDistributionService $salesmanDistributionService,
        private ConfigService $configService,
        private StatusService $statusService
    ) {}

    public function assign(People $company, People $client, string $linkType, ?People $uploader = null): ?People
    {
        if (!in_array($linkType, self::ASSIGNABLE_LINK_TYPES, true)) {
            return null;
        }

        $seller = $this->findCurrentSeller($company, $client);

        if (!$seller) {
            $seller = ($uploader && $this->isSalesmanOf($company, $uploader))
                ? $uploader
                : $this->salesmanDistributionService->discoverSalesman($company);
        }

        if (!$seller) {
            return null;
        }

        $this->peopleService->discoveryLink($seller, $client, 'sellers-client');

        if ($this->opportunityEnabled($company) && !$this->hasOpenOpportunity($company, $client)) {
            $this->createOpportunity($company, $seller, $client, $uploader);
        }

        return $seller;
    }

    public function opportunityEnabled(People $company): bool
    {
        $value = $this->configService->getConfig($company, self::OPPORTUNITY_CONFIG);

        if ($value === null || $value === '') {
            return true;
        }

        return !in_array(strtolower(trim((string) $value)), ['0', 'false', 'no', 'nao', 'off'], true);
    }

    private function isSalesmanOf(People $company, People $people): bool
    {
        return (bool) $this->manager->getRepository(PeopleLink::class)->findOneBy([
            'company' => $company,
            'people' => $people,
            'linkType' => 'salesman',
        ]);
    }

    // A client already in the portfolio of a seller of this company keeps that seller.
    private function findCurrentSeller(People $company, People $client): ?People
    {
        $link = $this->manager->createQueryBuilder()
            ->select('portfolio')
            ->from(PeopleLink::class, 'portfolio')
            ->join(
                PeopleLink::class,
                'salesman',
                'WITH',
                'salesman.people = portfolio.company AND salesman.company = :company AND salesman.linkType = :salesman'
            )
            ->andWhere('portfolio.people = :client')
            ->andWhere('portfolio.linkType = :portfolio')
            ->setParameter('company', $company)
            ->setParameter('client', $client)
            ->setParameter('salesman', 'salesman')
            ->setParameter('portfolio', 'sellers-client')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $link?->getCompany();
    }

    private function hasOpenOpportunity(People $company, People $client): bool
    {
        return (bool) $this->manager->createQueryBuilder()
            ->select('t.id')
            ->from(Task::class, 't')
            ->join('t.taskStatus', 's')
            ->andWhere('t.provider = :company')
            ->andWhere('t.client = :client')
            ->andWhere('t.type = :type')
            ->andWhere('s.realStatus IN (:openStatuses)')
            ->setParameter('company', $company)
            ->setParameter('client', $client)
            ->setParameter('type', 'relationship')
            ->setParameter('openStatuses', ['open', 'pending'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createOpportunity(People $company, People $seller, People $client, ?People $registeredBy): Task
    {
        $announce = [];
        foreach ($client->getPhone() as $phone) {
            $announce[] = $phone->getDdi() . $phone->getDdd() . $phone->getPhone();
        }

        $task = new Task();
        $task->setName(mb_substr((string) $client->getName(), 0, 50));
        $task->setType('relationship');
        $task->setProvider($company);
        $task->setTaskFor($seller);
        $task->setClient($client);
        // Imports run from the cron (no logged user): fall back to the seller.
        $task->setRegisteredBy($registeredBy ?? $seller);
        $task->setTaskStatus($this->statusService->discoveryStatus('open', 'open', 'relationship'));
        $task->setAnnounce($announce);

        $this->manager->persist($task);
        $this->manager->flush();

        return $task;
    }
}
