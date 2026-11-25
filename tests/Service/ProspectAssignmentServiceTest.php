<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\Task;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\ProspectAssignmentService;
use ControleOnline\Service\SalesmanDistributionService;
use ControleOnline\Service\StatusService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class ProspectAssignmentServiceTest extends TestCase
{
    private array $persisted = [];

    private function build(
        ?PeopleLink $salesmanLink,
        ?People $distributed,
        ?string $config,
        ?PeopleService $peopleService = null
    ): ProspectAssignmentService {
        $this->persisted = [];

        // no current seller and no open opportunity for the client
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])->getMock();
        $query->method('getOneOrNullResult')->willReturn(null);
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'join', 'andWhere', 'setParameter', 'setMaxResults', 'getQuery'])->getMock();
        foreach (['select', 'from', 'join', 'andWhere', 'setParameter', 'setMaxResults'] as $method) {
            $qb->method($method)->willReturnSelf();
        }
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($salesmanLink);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('createQueryBuilder')->willReturn($qb);
        $manager->method('getRepository')->willReturn($repository);
        $manager->method('persist')->willReturnCallback(function ($entity) {
            $this->persisted[] = $entity;
        });

        $distribution = $this->createMock(SalesmanDistributionService::class);
        $distribution->method('discoverSalesman')->willReturn($distributed);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        $statusService = $this->createMock(StatusService::class);
        $statusService->method('discoveryStatus')->willReturn($this->createStub(Status::class));

        return new ProspectAssignmentService(
            $manager,
            $peopleService ?? $this->createMock(PeopleService::class),
            $distribution,
            $configService,
            $statusService
        );
    }

    private function client(): People
    {
        $client = $this->createMock(People::class);
        $client->method('getName')->willReturn('ACME LTDA');
        $client->method('getPhone')->willReturn(new ArrayCollection());
        return $client;
    }

    public function testSellerUploadGoesToHisPortfolioWithOpportunity(): void
    {
        $company = new People();
        $seller = new People();
        $client = $this->client();

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->expects($this->once())->method('discoveryLink')->with($seller, $client, 'sellers-client');

        $service = $this->build(new PeopleLink(), new People(), null, $peopleService);
        $this->assertSame($seller, $service->assign($company, $client, 'prospect', $seller));

        $this->assertCount(1, $this->persisted);
        $task = $this->persisted[0];
        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame('relationship', $task->getType());
        $this->assertSame($seller, $task->getTaskFor());
        $this->assertSame($company, $task->getProvider());
        $this->assertSame('ACME LTDA', $task->getName());
    }

    public function testCompanyUploadUsesDistributionAndRespectsConfig(): void
    {
        $distributed = new People();

        $service = $this->build(null, $distributed, '0');
        $this->assertSame($distributed, $service->assign(new People(), $this->client(), 'client', new People()));
        $this->assertSame([], $this->persisted);
    }

    public function testProvidersAreNotAssigned(): void
    {
        $service = $this->build(new PeopleLink(), new People(), null);
        $this->assertNull($service->assign(new People(), $this->client(), 'provider', new People()));
    }
}
