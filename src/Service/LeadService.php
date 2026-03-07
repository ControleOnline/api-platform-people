<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\WhatsApp\Messages\WhatsAppMessage;
use ControleOnline\WhatsApp\Messages\WhatsAppContent;

class LeadService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TaskService $taskService,
        private ConfigService $configService,
        private TaskInterationService $taskInterationService
    ) {}

    /**
     * Busca o limite máximo de tarefas para vendedores da empresa.
     * Default: 10
     */
    private function getMaxTasksAllowed(People $company): int
    {
        $config = $this->configService->getConfig($company, 'salesman-max-tasks');
        return $config ? (int) $config : 10;
    }

    public function distributeLeads(int $limit = 10): int
    {
        $created = 0;

        // 1. Busca os vendedores que têm espaço na agenda (respeitando o limite da empresa)
        $availableSalesmen = $this->getSalesmenWithRoom($limit);

        foreach ($availableSalesmen as $data) {
            if ($created >= $limit) break;

            $salesman = $data['salesman'];
            $company  = $data['company'];

            // 2. Busca um lead que ainda não foi abordado por esta empresa
            $lead = $this->getFreshLeadForCompany($company);

            if ($lead) {
                // Cria a task e a interação automática de WhatsApp
                $this->createOpportunityWithInteraction($company, $salesman, $lead);

                $created++;

                // Flush imediato para que a contagem de tasks reflita na próxima iteração
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

    private function getSalesmenWithRoom(int $limit): array
    {
        $qb = $this->manager->createQueryBuilder();

        // Buscamos vendedores e a contagem de suas tarefas abertas
        $results = $qb->select('s as salesman, c as company, COUNT(t.id) as current_tasks')
            ->from(PeopleLink::class, 'pl')
            ->join('pl.people', 's')
            ->join('pl.company', 'c')
            ->leftJoin(Task::class, 't', 'WITH', 't.taskFor = s AND t.company = c AND t.type = :type')
            ->leftJoin('t.taskStatus', 'ts')
            ->where('pl.linkType = :salesmanRole')
            ->andWhere('ts.realStatus = :openStatus OR t.id IS NULL')
            ->groupBy('s.id, c.id')
            ->orderBy('current_tasks', 'ASC')
            ->setParameter('salesmanRole', 'salesman')
            ->setParameter('type', 'opportunity')
            ->setParameter('openStatus', 'open')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Filtra os resultados comparando a contagem atual com o limite dinâmico da empresa
        return array_filter($results, function ($data) {
            $maxAllowed = $this->getMaxTasksAllowed($data['company']);
            return (int)$data['current_tasks'] < $maxAllowed;
        });
    }

    private function getFreshLeadForCompany(People $company): ?People
    {
        $qb = $this->manager->createQueryBuilder();

        // Subquery para excluir leads que já possuem tarefa do tipo 'opportunity' nesta empresa
        $subQB = $this->manager->createQueryBuilder();
        $subQB->select('identity(st.client)')
            ->from(Task::class, 'st')
            ->where('st.company = :company')
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
}
