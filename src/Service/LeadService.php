<?php

/*
 * Contract imported from AGENTS.md
 * ## Escopo
 * - Modulo de pessoas e empresas.
 * - Cobre `People`, documentos, emails, dominios, empresas, pacotes e fluxo base de criacao de conta.
 *
 * ## Quando usar
 * - Prompts sobre pessoa, empresa, documentos, contatos, dominio, company context e vinculos principais de cadastro.
 *
 * ## Regras de vinculo
 * - `people_link` e o catalogo oficial de relacionamento entre pessoa e empresa.
 * - Roles humanas explicitas do modulo: `employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`, `courier`.
 * - Roles comerciais explicitas do modulo: `client`, `provider`, `franchisee`.
 * - `family` e `sellers-client` nao entram como role humana de API.
 * - `courier` e o vinculo humano usado para entregadores da loja; nao reutilizar `carrier` como sinonimo de cadastro.
 * - `ROLE_SUPER` so existe quando o vinculo direto com a empresa principal for `owner`.
 *
 * ## Regras de acesso
 * - A pessoa fisica so tem permissao operacional na empresa do vinculo direto.
 * - Subidas de nivel na arvore de empresas servem apenas para validar a cadeia comercial ate a principal.
 * - `client` nao concede permissao operacional humana; ele apenas habilita o acesso comercial da empresa ao painel.
 * - `permission` retornado por company context deve refletir o link direto da pessoa com a empresa e a cadeia comercial valida.
 * - O filtro principal de dados do modulo mora em `PeopleService::securityFilter()` e helpers derivados.
 * - `People` nao pode expor por serializacao aninhada dados sensiveis de `User` para leitores mais amplos do que a politica direta de `User`.
 * - Campos como `username`, `apiKey`, hash de recuperacao, credenciais ou identificadores equivalentes de `User` nao podem sair em grupos amplos como `people:read`.
 * - Se `People` mantiver relacao com `User`, essa relacao so pode aparecer em grupo e operacao com autorizacao tao forte quanto a leitura direta de `User`.
 * - Operacoes amplas ou publicas de `People`, incluindo `Get` com `PUBLIC_ACCESS`, nunca podem se tornar caminho lateral para leitura de credenciais ou segredos de `User`.
 * - Listagens de `People` consumidas por `DefaultTable` React precisam de `CustomOrFilter`, `OrderFilter` e `DateFilter` alinhados com os campos declarados no store, com datas ordenando pelo valor persistido.
 *
 * ## Regras de vendedores e comissao
 * - O vinculo `sellers-client` em `people_link` e sensivel porque revela e altera a relacao comercial entre cliente e vendedor.
 * - Fora do contexto `APP_TYPE=MANAGER`, o ecossistema pode identificar quem e o vendedor vinculado ao cliente, mas nao pode expor `comission` nem `minimum_comission`.
 * - Operacoes de adicionar, editar, remover ou trocar vendedores vinculados ao cliente so podem ser liberadas no contexto `MANAGER`.
 * - O backend nao pode confiar apenas em ocultacao de campos no front para proteger `people_link`.
 * - Toda leitura e toda escrita de `people_link`, especialmente para `linkType=sellers-client`, precisa de `securityFilter` proprio no service equivalente da entidade ou protecao explicita de mesmo efeito cobrindo leitura e gravacao.
 * - `ROLE_HUMAN` isolado nao e protecao suficiente para esse recurso.
 *
 * ## Responsabilidades
 * - `people` e dono dos dados cadastrais e dos relacionamentos pessoa/empresa.
 * - `PeopleRoleService` resolve roles e empresas acessiveis a partir de `people_link`.
 * - `PeopleService` aplica o recorte de dados por empresa em `securityFilter`.
 *
 * ## Regra de extra_data
 * - `extra_data` e `extra_fields` nao podem guardar snapshot rico de pessoa, empresa, integracao, loja ou configuracao quando o dado ja tiver destino canonico em `People`, `people_link`, `Config` ou JSON materializado da entidade.
 * - Nesta area, `extra_data` so pode carregar IDs, codigos remotos e chaves de vinculo que ainda nao tenham coluna ou relacao canonica equivalente.
 * - Quando um dado ja estiver materializado, a persistencia nova deve ir para a entidade dona e a limpeza de legado deve remover o respectivo par `extra_data`/`extra_fields`.
 *
 * ## Limites
 * - Autenticacao, token e sessao pertencem a `users`.
 */


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
