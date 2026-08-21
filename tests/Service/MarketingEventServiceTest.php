<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\MarketingEvent;
use ControleOnline\Entity\People;
use ControleOnline\Repository\MarketingEventRepository;
use ControleOnline\Service\MarketingEventService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MarketingEventServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private MarketingEventRepository $repository;
    private MarketingEventService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(MarketingEventRepository::class);
        $this->service = new MarketingEventService($this->em, $this->repository);
    }

    public function testCreateFromPayloadPersistsValidEvent(): void
    {
        $this->repository->method('findOneByIdempotencyKey')->willReturn(null);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(MarketingEvent::class));
        $this->em->expects($this->once())->method('flush');

        $event = $this->service->createFromPayload([
            'event_name' => 'page_view',
            'visitor_id' => 'vis-abc-123',
            'timestamp' => '2026-08-20T10:00:00+00:00',
            'page_url' => 'https://example.com/landing',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring',
            'email' => 'lead@example.com',
        ]);

        $this->assertSame('page_view', $event->getEventName());
        $this->assertSame('vis-abc-123', $event->getVisitorId());
        $this->assertSame('google', $event->getUtmSource());
        $this->assertSame('lead@example.com', $event->getEmail());
        $this->assertNotEmpty($event->getIdempotencyKey());
    }

    public function testCreateFromPayloadIsIdempotent(): void
    {
        $existing = new MarketingEvent();
        $existing->setEventName('form_submit');
        $existing->setVisitorId('vis-1');
        $existing->setIdempotencyKey('already');

        $this->repository->method('findOneByIdempotencyKey')->willReturn($existing);
        $this->em->expects($this->never())->method('persist');

        $event = $this->service->createFromPayload([
            'event_name' => 'form_submit',
            'visitor_id' => 'vis-1',
            'timestamp' => '2026-08-20T11:00:00Z',
        ]);

        $this->assertSame($existing, $event);
    }

    public function testRejectsInvalidEventName(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->service->createFromPayload([
            'event_name' => 'click_bomb',
            'visitor_id' => 'v1',
        ]);
    }

    public function testRejectsMissingVisitorId(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->service->createFromPayload([
            'event_name' => 'page_view',
        ]);
    }

    public function testAssociateVisitorToPeopleDelegatesToRepository(): void
    {
        $people = $this->createMock(People::class);
        $this->repository->expects($this->once())
            ->method('linkVisitorToPeople')
            ->with('vis-xyz', $people)
            ->willReturn(3);

        $count = $this->service->associateVisitorToPeople('vis-xyz', $people);
        $this->assertSame(3, $count);
    }

    public function testAssociateEmptyVisitorReturnsZero(): void
    {
        $people = $this->createMock(People::class);
        $this->repository->expects($this->never())->method('linkVisitorToPeople');
        $this->assertSame(0, $this->service->associateVisitorToPeople('  ', $people));
    }
}
