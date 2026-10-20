<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\CommissionService;
use ControleOnline\Service\Contract\PeriodicReceivableServiceInterface;
use ControleOnline\Service\RoyaltiesService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class PeriodicReceivableServiceTest extends TestCase
{
    public function testBothServicesImplementSharedInterface(): void
    {
        $this->assertInstanceOf(PeriodicReceivableServiceInterface::class, new CommissionService());
        $this->assertInstanceOf(PeriodicReceivableServiceInterface::class, new RoyaltiesService());
    }

    public function testInvoiceTypeDiscriminators(): void
    {
        $this->assertSame('comission', (new CommissionService())->getInvoiceType());
        $this->assertSame('royalties', (new RoyaltiesService())->getInvoiceType());
    }

    public function testComputeAmountAppliesRateAndRounds(): void
    {
        $link = new PeopleLink();
        $link->setComission(10); // 10%

        $svc = new CommissionService();
        $this->assertSame(25.0, $svc->computeAmount(250.0, $link));
        $this->assertSame(10.0, $svc->computeAmount(100.0, $link));
    }

    public function testComputeAmountRespectsMinimumCommission(): void
    {
        $link = new PeopleLink();
        $link->setComission(1); // 1% of 100 = 1
        $link->setMinimumComission(5);

        $svc = new CommissionService();
        $this->assertSame(5.0, $svc->computeAmount(100.0, $link));
    }

    public function testClientOverrideRateTakesPrecedence(): void
    {
        $link = new PeopleLink();
        $link->setComission(10);

        $override = new PeopleLink();
        $override->setComission(20);

        $svc = new CommissionService();
        $this->assertSame(20.0, $svc->computeAmount(100.0, $link, $override));
    }

    public function testWeeklyClosingPeriodAndDueDateWithPaymentTerm(): void
    {
        $link = new PeopleLink();
        $link->setClosingPeriod('weekly');
        $link->setPaymentTermDays(3);

        $svc = new CommissionService();
        // Wednesday 2026-08-19
        $ref = new DateTimeImmutable('2026-08-19 12:00:00');

        $start = $svc->resolvePeriodStart($link, $ref);
        $end = $svc->resolvePeriodEnd($link, $ref);
        $due = $svc->resolveDueDate($link, $ref);

        $this->assertSame('2026-08-17', $start->format('Y-m-d')); // Monday
        $this->assertSame('2026-08-23', $end->format('Y-m-d'));   // Sunday
        $this->assertSame('2026-08-26', $due->format('Y-m-d'));   // Sunday + 3
    }

    public function testMonthlyClosingPeriodDefault(): void
    {
        $link = new PeopleLink();
        // default monthly, payment_term 0

        $svc = new RoyaltiesService();
        $ref = new DateTimeImmutable('2026-08-19');

        $start = $svc->resolvePeriodStart($link, $ref);
        $end = $svc->resolvePeriodEnd($link, $ref);
        $due = $svc->resolveDueDate($link, $ref);

        $this->assertSame('2026-08-01', $start->format('Y-m-d'));
        $this->assertSame('2026-08-31', $end->format('Y-m-d'));
        $this->assertSame('2026-08-31', $due->format('Y-m-d'));
    }

    public function testBiweeklyFirstHalf(): void
    {
        $link = new PeopleLink();
        $link->setClosingPeriod('biweekly');

        $svc = new CommissionService();
        $ref = new DateTimeImmutable('2026-08-10');

        $this->assertSame('2026-08-01', $svc->resolvePeriodStart($link, $ref)->format('Y-m-d'));
        $this->assertSame('2026-08-15', $svc->resolvePeriodEnd($link, $ref)->format('Y-m-d'));
    }

    public function testBiweeklySecondHalf(): void
    {
        $link = new PeopleLink();
        $link->setClosingPeriod('biweekly');

        $svc = new CommissionService();
        $ref = new DateTimeImmutable('2026-08-20');

        $this->assertSame('2026-08-16', $svc->resolvePeriodStart($link, $ref)->format('Y-m-d'));
        $this->assertSame('2026-08-31', $svc->resolvePeriodEnd($link, $ref)->format('Y-m-d'));
    }

    public function testResolveOpenInvoiceUsesInjectedResolver(): void
    {
        $invoice = new stdClass();
        $invoice->id = 42;

        $calls = [];
        $resolver = function (PeopleLink $link, DateTimeInterface $ref, string $type) use (&$calls, $invoice) {
            $calls[] = [$type, $ref->format('Y-m-d')];
            return $invoice;
        };

        $svc = new CommissionService($resolver);
        $link = new PeopleLink();
        $result = $svc->resolveOpenInvoice($link, new DateTimeImmutable('2026-08-19'));

        $this->assertSame($invoice, $result);
        $this->assertSame([['comission', '2026-08-19']], $calls);
    }

    public function testResolveOpenInvoiceWithoutResolverThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new CommissionService())->resolveOpenInvoice(new PeopleLink(), new DateTimeImmutable());
    }

    public function testAttachOrderUsesInjectedAttacher(): void
    {
        $attached = [];
        $attacher = function (object $invoice, object $order) use (&$attached) {
            $attached[] = [$invoice, $order];
        };

        $svc = new RoyaltiesService(null, $attacher);
        $inv = new stdClass();
        $ord = new stdClass();
        $svc->attachOrder($inv, $ord);

        $this->assertCount(1, $attached);
        $this->assertSame($inv, $attached[0][0]);
        $this->assertSame($ord, $attached[0][1]);
    }

    public function testAggregationScenarioTwoOrdersSamePeriodSameInvoice(): void
    {
        $invoices = [];
        $resolver = function (PeopleLink $link, DateTimeInterface $ref, string $type) use (&$invoices) {
            $key = $type . ':' . $ref->format('Y-m');
            if (!isset($invoices[$key])) {
                $inv = new stdClass();
                $inv->type = $type;
                $inv->orders = [];
                $invoices[$key] = $inv;
            }
            return $invoices[$key];
        };
        $attacher = function (object $invoice, object $order) {
            $invoice->orders[] = $order;
        };

        $svc = new CommissionService($resolver, $attacher);
        $link = new PeopleLink();
        $link->setClosingPeriod('monthly');
        $link->setComission(10);

        $ref1 = new DateTimeImmutable('2026-08-05');
        $ref2 = new DateTimeImmutable('2026-08-20');

        $inv1 = $svc->resolveOpenInvoice($link, $ref1);
        $inv2 = $svc->resolveOpenInvoice($link, $ref2);
        $this->assertSame($inv1, $inv2);

        $orderA = new stdClass();
        $orderA->amount = 100;
        $orderB = new stdClass();
        $orderB->amount = 200;

        $svc->attachOrder($inv1, $orderA);
        $svc->attachOrder($inv2, $orderB);

        $this->assertCount(2, $inv1->orders);
        $total = $svc->computeAmount(100.0, $link) + $svc->computeAmount(200.0, $link);
        $this->assertSame(30.0, $total);
    }
}
