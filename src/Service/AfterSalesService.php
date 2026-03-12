<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Task;
use ControleOnline\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\WhatsApp\Messages\WhatsAppMessage;
use ControleOnline\WhatsApp\Messages\WhatsAppContent;
use Doctrine\ORM\QueryBuilder;
use ControleOnline\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class AfterSalesService implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TaskService $taskService,
        private ConfigService $configService,
        private TaskInterationService $taskInterationService,
        private Security $security
    ) {}

    /**
     * Busca o limite de tarefas configurado para a empresa.
     * Default: 10
     */
    private function getMaxTasksAllowed(People $company): int
    {
        $config = $this->configService->getConfig($company, 'salesman-max-tasks');
        return $config ? (int) $config : 10;
    }

    private function getOwnerSubquery(string $clientAlias): string
    {
        return "COALESCE(
            (SELECT as_l.company FROM ControleOnline\Entity\PeopleLink as_l 
             WHERE as_l.people = $clientAlias AND as_l.linkType = 'after-sales' LIMIT 1),
            (SELECT sm_l.company FROM ControleOnline\Entity\PeopleLink sm_l 
             WHERE sm_l.people = $clientAlias AND sm_l.linkType = 'salesman' LIMIT 1)
        )";
    }

    private function getRevenueSubquery(string $clientAlias): QueryBuilder
    {
        return $this->manager->createQueryBuilder()
            ->select('SUM(i_sub.price)')
            ->from(Invoice::class, 'i_sub')
            ->join('i_sub.status', 'ist_sub')
            ->where("i_sub.payer = $clientAlias")
            ->andWhere('i_sub.receiver = :company')
            ->andWhere('ist_sub.realStatus = :paidStatus')
            ->andWhere('i_sub.invoice_date >= :revenueStartDate');
    }

    public function processAfterSales(?People $company, ?int $buffer = 10): int
    {
        $created = 0;
        // O buffer aqui limita quantos responsáveis processaremos por execução
        $responsibles = $this->getResponsiblesWithRoom($company, $buffer);

        foreach ($responsibles as $data) {
            if ($created >= $buffer) break;

            $responsible = $data['responsible'];
            $company     = $data['company'];

            $profiles    = $this->configService->getConfig($company, 'after-sales-profiles', true) ?? [];
            $revenueDays = (int) ($this->configService->getConfig($company, 'after-sales-revenue-period') ?? 90);

            usort($profiles, fn($a, $b) => $b['maxRevenue'] <=> $a['maxRevenue']);

            foreach ($profiles as $profile) {
                if ($created >= $buffer) break;

                $client = $this->getEligibleClient($responsible, $company, $profile, $revenueDays);

                if ($client) {
                    $this->createRelationshipTaskWithInteraction($company, $responsible, $client);
                    $created++;
                    break;
                }
            }
        }
        return $created;
    }

    private function getEligibleClient(People $responsible, People $company, array $profile, int $revenueDays): ?People
    {
        $contactDays      = (int) $profile['days'];
        $minRevenue       = (float) $profile['maxRevenue'];
        $contactThreshold = (new \DateTime())->modify("-$contactDays days");
        $revenueStartDate = (new \DateTime())->modify("-$revenueDays days");

        $revenueDQL = $this->getRevenueSubquery('c')->getDQL();

        $qb = $this->manager->createQueryBuilder();
        $qb->select('c')
            ->from(People::class, 'c')
            ->join(PeopleLink::class, 'pl_empresa', 'WITH', 'pl_empresa.people = c AND pl_empresa.company = :company')
            ->leftJoin(Task::class, 't', 'WITH', 't.client = c AND t.taskFor = :responsible AND t.type = :type')
            ->where('pl_empresa.linkType = :clientType')
            ->andWhere(':responsible = (' . $this->getOwnerSubquery('c.id') . ')')
            ->andWhere('t.id IS NULL OR t.createdAt < :contactThreshold')
            ->andWhere("($revenueDQL) >= :minRevenue")

            ->setParameter('company', $company)
            ->setParameter('responsible', $responsible)
            ->setParameter('clientType', 'client')
            ->setParameter('type', 'relationship')
            ->setParameter('paidStatus', 'paid')
            ->setParameter('minRevenue', $minRevenue)
            ->setParameter('contactThreshold', $contactThreshold)
            ->setParameter('revenueStartDate', $revenueStartDate)
            ->orderBy("($revenueDQL)", 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function getResponsiblesWithRoom(?People $company, ?int $limit = 10): array
    {
        $qb = $this->manager->createQueryBuilder()
            ->select('r as responsible, comp as company, COUNT(t.id) as current_tasks')
            ->from(PeopleLink::class, 'pl')
            ->join('pl.people', 'r')
            ->join('pl.company', 'comp')
            ->leftJoin(Task::class, 't', 'WITH', 't.taskFor = r AND t.company = comp AND t.type = :type')
            ->leftJoin('t.taskStatus', 'ts')
            ->where('pl.linkType IN (:roles)')
            ->andWhere('ts.realStatus = :openStatus OR t.id IS NULL')
            ->andWhere('t.id IS NULL OR r.id = (' . $this->getOwnerSubquery('t.client') . ')')
            ->groupBy('r.id, comp.id')
            ->orderBy('current_tasks', 'ASC')
            ->setParameter('roles', ['salesman', 'after-sales'])
            ->setParameter('type', 'relationship')
            ->setParameter('openStatus', 'open')
            ->setMaxResults($limit);

        if ($company) {
            $qb->andWhere('comp = :company')
                ->setParameter('company', $company);
        }

        $results = $qb->getQuery()
            ->getResult();

        // Filtramos manualmente para respeitar o limite dinâmico de cada empresa
        return array_filter($results, function ($data) {
            $maxAllowed = $this->getMaxTasksAllowed($data['company']);
            return $data['current_tasks'] < $maxAllowed;
        });
    }

    private function createRelationshipTaskWithInteraction(People $company, People $responsible, People $client): void
    {
        $task = $this->taskService->addTask($company, $responsible, $client, 'relationship');

        $messageContent = new WhatsAppContent();
        $messageContent->setBody(
            "Olá {$client->getName()},\n" .
                "Como estão as coisas por aí? Precisa de algo?"
        );

        $message = new WhatsAppMessage();
        $message->setAction('sendMessage');
        $message->setMessageContent($messageContent);

        $this->taskInterationService->addInteration(
            $responsible,
            $message,
            $task,
            'relationship',
            'public'
        );

        $this->manager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityChangedEvent::class => 'onEntityChanged',
        ];
    }

    public function onEntityChanged(EntityChangedEvent $event)
    {
        $oldEntity = $event->getOldEntity();
        $entity = $event->getEntity();

        $currentUser = $this->security->getToken()?->getUser();

        if (!$entity instanceof Task || !$currentUser)
            return;
        
        $this->processAfterSales($entity->getProvider(), 1);
    }
}
