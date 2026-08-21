<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\CommissionService;
use ControleOnline\Service\PeriodicReceivableDispatcher;
use ControleOnline\Service\RoyaltiesService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PeriodicReceivableDispatcherTest extends TestCase
{
    public function testDispatchAggregatesWhenLinkResolved(): void
    {
        $link = new PeopleLink();
        $link->setComission(10);
        $link->setClosingPeriod('monthly');
        $link->setPaymentTermDays(5);

        $aggregate = new stdClass();
        $aggregate->orders = [];
        $aggregate->amount = 0.0;

        $commission = new CommissionService(
            invoiceResolver: static function (PeopleLink $l, $ref, string $type) use ($aggregate) {
                return $aggregate;
            },
            orderAttacher: static function (object $invoice, object $order) use ($aggregate): void {
                $aggregate->orders[] = $order;
            },
            sourceTypeReader: static fn () => 'sale',
        );

        $source = new stdClass();
        $source->price = 200.0;
        $order = new stdClass();
        $order->id = 99;

        $dispatcher = new PeriodicReceivableDispatcher(
            services: [$commission],
            linkResolver: static function (object $invoice, string $role) use ($link): ?PeopleLink {
                return $role === 'salesman' ? $link : null;
            },
            amountReader: static fn (object $invoice): float => (float) $invoice->price,
            orderReader: static fn () => $order,
            referenceDateReader: static fn () => new DateTimeImmutable('2026-08-15'),
        );

        $touched = $dispatcher->dispatch($source);

        $this->assertCount(1, $touched);
        $this->assertSame($aggregate, $touched[0]);
        $this->assertCount(1, $aggregate->orders);
        $this->assertSame($order, $aggregate->orders[0]);
    }

    public function testDispatchAggregatesTwoOrdersSamePeriod(): void
    {
        $link = new PeopleLink();
        $link->setComission(10);
        $link->setClosingPeriod('monthly');

        $aggregate = new stdClass();
        $aggregate->orders = [];

        $commission = new CommissionService(
            invoiceResolver: static fn () => $aggregate,
            orderAttacher: static function (object $invoice, object $order) use ($aggregate): void {
                $aggregate->orders[] = $order;
            },
            sourceTypeReader: static fn () => 'sale',
        );

        $dispatcher = new PeriodicReceivableDispatcher(
            services: [$commission],
            linkResolver: static fn () => $link,
            amountReader: static fn (object $i): float => (float) $i->price,
            orderReader: static fn (object $i) => $i->order,
            referenceDateReader: static fn () => new DateTimeImmutable('2026-08-10'),
        );

        $o1 = new stdClass();
        $o1->id = 1;
        $s1 = new stdClass();
        $s1->price = 100.0;
        $s1->order = $o1;

        $o2 = new stdClass();
        $o2->id = 2;
        $s2 = new stdClass();
        $s2->price = 50.0;
        $s2->order = $o2;

        $dispatcher->dispatch($s1);
        $dispatcher->dispatch($s2);

        $this->assertCount(2, $aggregate->orders);
        $this->assertSame($o1, $aggregate->orders[0]);
        $this->assertSame($o2, $aggregate->orders[1]);
    }

    public function testDispatchSkipsWhenNoLink(): void
    {
        $commission = new CommissionService(
            invoiceResolver: static function () {
                self::fail('should not resolve invoice without link');
            },
            sourceTypeReader: static fn () => 'sale',
        );

        $dispatcher = new PeriodicReceivableDispatcher(
            services: [$commission],
            linkResolver: static fn () => null,
            amountReader: static fn () => 100.0,
        );

        $touched = $dispatcher->dispatch(new stdClass());
        $this->assertSame([], $touched);
    }

    public function testDispatchRunsBothCommissionAndRoyaltiesWhenSupported(): void
    {
        $salesman = new PeopleLink();
        $salesman->setComission(5);
        $franchisee = new PeopleLink();
        $franchisee->setComission(8);

        $commAgg = new stdClass();
        $royAgg = new stdClass();
        $commAgg->orders = [];
        $royAgg->orders = [];

        $commission = new CommissionService(
            invoiceResolver: static fn () => $commAgg,
            orderAttacher: static function (object $inv, object $order) use ($commAgg): void {
                $commAgg->orders[] = $order;
            },
            sourceTypeReader: static fn () => 'sale',
        );
        $royalties = new RoyaltiesService(
            invoiceResolver: static fn () => $royAgg,
            orderAttacher: static function (object $inv, object $order) use ($royAgg): void {
                $royAgg->orders[] = $order;
            },
            sourceTypeReader: static fn () => 'sale',
        );

        $order = new stdClass();
        $source = new stdClass();
        $source->price = 1000.0;

        $dispatcher = new PeriodicReceivableDispatcher(
            services: [$commission, $royalties],
            linkResolver: static function (object $invoice, string $role) use ($salesman, $franchisee): ?PeopleLink {
                return match ($role) {
                    'salesman' => $salesman,
                    'franchisee' => $franchisee,
                    default => null,
                };
            },
            amountReader: static fn (object $i): float => (float) $i->price,
            orderReader: static fn () => $order,
            referenceDateReader: static fn () => new DateTimeImmutable('2026-08-01'),
        );

        $touched = $dispatcher->dispatch($source);
        $this->assertCount(2, $touched);
        $this->assertCount(1, $commAgg->orders);
        $this->assertCount(1, $royAgg->orders);
    }

    public function testDispatchFailOpenOnServiceException(): void
    {
        $failing = new CommissionService(
            invoiceResolver: static function () {
                throw new \RuntimeException('boom');
            },
            sourceTypeReader: static fn () => 'sale',
        );

        $dispatcher = new PeriodicReceivableDispatcher(
            services: [$failing],
            linkResolver: static fn () => new PeopleLink(),
            amountReader: static fn () => 10.0,
        );

        $touched = $dispatcher->dispatch(new stdClass());
        $this->assertSame([], $touched);
    }
}
