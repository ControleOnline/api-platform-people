<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\WhatsApp\Messages\WhatsAppMessage;
use ControleOnline\WhatsApp\Messages\WhatsAppContent;
use ControleOnline\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class LeadService implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TaskService $taskService,
        private ConfigService $configService,
        private TaskInterationService $taskInterationService,
        private Security $security
    ) {}

    private function getMaxTasksAllowed(People $company): int
    {
        $config = $this->configService->getConfig($company, 'salesman-max-tasks');
        return $config ? (int) $config : 10;
    }

    public function distributeLeads(?People $company, ?int $limit = 10): int
    {
        $created = 0;

        $availableSalesmen = $this->getSalesmenWithRoom($company, $limit);

        foreach ($availableSalesmen as $data) {
            if ($created >= $limit) {
                break;
            }

            $salesman = $data['salesman'];
            $companyData = $data['company'];

            if (!$companyData instanceof People) {
                continue;
            }

            $lead = $this->getFreshLeadForCompany($companyData);

            if ($lead) {
                $this->createOpportunityWithInteraction($companyData, $salesman, $lead);
                $created++;
                $this->manager->flush();
            }
        }

        return $created;
    }

    private function createOpportunityWithInteraction(People $company, People $salesman, People $lead): void
    {
        $task = $this->taskService->addTask($company, $salesman, $lead, 'opportunity');

        $messageContent = new WhatsAppContent();
        $messageContent->setBody(
            "Olá {$lead->getName()},\n" .
                "Sou {$salesman->getName()}, da {$company->getAlias()}. Podemos conversar sobre soluções para sua empresa?"
        );

        $message = new WhatsAppMessage();
        $message->setAction('sendMessage');
        $message->setMessageContent($messageContent);

        $this->taskInterationService->addInteration(
            $salesman,
            $message,
            $task,
            'opportunity',
            'public'
        );
    }

    private function getSalesmenWithRoom(?People $company, ?int $limit = 10): array
    {
        $qb = $this->manager->createQueryBuilder()
            ->select('pl, s, c, COUNT(t.id) as current_tasks')
            ->from(PeopleLink::class, 'pl')
            ->join('pl.people', 's')
            ->join('pl.company', 'c')
            ->leftJoin(Task::class, 't', 'WITH', 't.taskFor = s AND t.provider = c AND t.type = :type')
            ->leftJoin('t.taskStatus', 'ts')
            ->where('pl.linkType = :salesmanRole')
            ->andWhere('ts.realStatus = :openStatus OR t.id IS NULL')
            ->groupBy('pl.id, s.id, c.id')
            ->orderBy('current_tasks', 'ASC')
            ->setParameter('salesmanRole', 'salesman')
            ->setParameter('type', 'opportunity')
            ->setParameter('openStatus', 'open')
            ->setMaxResults($limit);

        if ($company) {
            $qb->andWhere('c = :company')
                ->setParameter('company', $company);
        }

        $rows = $qb->getQuery()->getResult();

        $results = [];

        foreach ($rows as $row) {
            $salesman = $row[1] ?? null;
            $comp = $row[2] ?? null;
            $currentTasks = $row['current_tasks'] ?? 0;

            if (!$comp instanceof People || !$salesman instanceof People) {
                continue;
            }

            $maxAllowed = $this->getMaxTasksAllowed($comp);

            if ($currentTasks < $maxAllowed) {
                $results[] = [
                    'salesman' => $salesman,
                    'company' => $comp,
                    'current_tasks' => $currentTasks
                ];
            }
        }

        return $results;
    }

    private function getFreshLeadForCompany(People $company): ?People
    {
        $qb = $this->manager->createQueryBuilder();

        $subQB = $this->manager->createQueryBuilder();
        $subQB->select('IDENTITY(st.client)')
            ->from(Task::class, 'st')
            ->where('st.provider = :company')
            ->andWhere('st.type = :type');

        $result = $qb->select('pl_lead')
            ->from(PeopleLink::class, 'pl_lead')
            ->join('pl_lead.people', 'l')
            ->where('pl_lead.company = :company')
            ->andWhere('pl_lead.linkType = :leadRole')
            ->andWhere($qb->expr()->notIn('l.id', $subQB->getDQL()))
            ->setParameter('company', $company)
            ->setParameter('leadRole', 'lead')
            ->setParameter('type', 'opportunity')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result->getPeople() : null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityChangedEvent::class => 'onEntityChanged',
        ];
    }

    public function onEntityChanged(EntityChangedEvent $event)
    {
        $entity = $event->getEntity();
        $currentUser = $this->security->getToken()?->getUser();

        if (!$entity instanceof Task || !$currentUser)
            return;


        $this->distributeLeads($entity->getProvider(), 1);
    }
}
