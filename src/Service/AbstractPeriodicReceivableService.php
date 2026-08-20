<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\Contract\PeriodicReceivableServiceInterface;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Shared period math + amount rules for commission and royalties services.
 *
 * Persistence of Invoice is delegated to subclasses / injected collaborators
 * so this package does not depend on api-platform-financial at compile time.
 */
abstract class AbstractPeriodicReceivableService implements PeriodicReceivableServiceInterface
{
    abstract public function getInvoiceType(): string;

    abstract public function supports(object $sourceInvoice): bool;

    abstract public function resolveOpenInvoice(PeopleLink $link, DateTimeInterface $referenceDate): object;

    abstract public function attachOrder(object $aggregateInvoice, object $order): void;

    public function computeAmount(float $baseAmount, PeopleLink $link, ?PeopleLink $clientOverride = null): float
    {
        $rate = $this->resolveCommissionRate($link, $clientOverride);
        $minimum = $this->resolveMinimumCommission($link, $clientOverride);

        $amount = $baseAmount * ($rate / 100.0);
        if ($minimum > 0 && $amount < $minimum) {
            $amount = $minimum;
        }

        return round($amount, 2);
    }

    public function resolveDueDate(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface
    {
        $periodEnd = $this->resolvePeriodEnd($link, $referenceDate);
        $days = method_exists($link, 'getPaymentTermDays')
            ? $link->getPaymentTermDays()
            : 0;

        return DateTimeImmutable::createFromInterface($periodEnd)
            ->modify(sprintf('+%d days', max(0, $days)));
    }

    public function resolvePeriodStart(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface
    {
        $period = $this->normalizeClosingPeriod($link);
        $ref = DateTimeImmutable::createFromInterface($referenceDate)->setTime(0, 0, 0);

        return match ($period) {
            'daily' => $ref,
            'weekly' => $ref->modify('monday this week'),
            'biweekly' => $this->biweeklyPeriodStart($ref),
            default => $ref->modify('first day of this month'),
        };
    }

    public function resolvePeriodEnd(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface
    {
        $period = $this->normalizeClosingPeriod($link);
        $ref = DateTimeImmutable::createFromInterface($referenceDate)->setTime(23, 59, 59);

        return match ($period) {
            'daily' => $ref,
            'weekly' => $ref->modify('sunday this week'),
            'biweekly' => $this->biweeklyPeriodEnd($ref),
            default => $ref->modify('last day of this month'),
        };
    }

    protected function resolveCommissionRate(PeopleLink $link, ?PeopleLink $clientOverride): float
    {
        if ($clientOverride !== null && method_exists($clientOverride, 'getComission')) {
            $override = (float) $clientOverride->getComission();
            if ($override > 0) {
                return $override;
            }
        }

        return method_exists($link, 'getComission') ? (float) $link->getComission() : 0.0;
    }

    protected function resolveMinimumCommission(PeopleLink $link, ?PeopleLink $clientOverride): float
    {
        if ($clientOverride !== null && method_exists($clientOverride, 'getMinimumComission')) {
            $override = (float) $clientOverride->getMinimumComission();
            if ($override > 0) {
                return $override;
            }
        }

        return method_exists($link, 'getMinimumComission') ? (float) $link->getMinimumComission() : 0.0;
    }

    protected function normalizeClosingPeriod(PeopleLink $link): string
    {
        $period = method_exists($link, 'getClosingPeriod')
            ? strtolower(trim((string) $link->getClosingPeriod()))
            : 'monthly';

        $allowed = defined(PeopleLink::class . '::CLOSING_PERIODS')
            ? PeopleLink::CLOSING_PERIODS
            : ['daily', 'weekly', 'biweekly', 'monthly'];

        if ($period === '' || !in_array($period, $allowed, true)) {
            return 'monthly';
        }

        return $period;
    }

    /**
     * Biweekly windows: days 1–15 and 16–end of month.
     */
    protected function biweeklyPeriodStart(DateTimeImmutable $ref): DateTimeImmutable
    {
        $day = (int) $ref->format('j');
        if ($day <= 15) {
            return $ref->modify('first day of this month');
        }

        return $ref->setDate((int) $ref->format('Y'), (int) $ref->format('n'), 16);
    }

    protected function biweeklyPeriodEnd(DateTimeImmutable $ref): DateTimeImmutable
    {
        $day = (int) $ref->format('j');
        if ($day <= 15) {
            return $ref->setDate((int) $ref->format('Y'), (int) $ref->format('n'), 15)->setTime(23, 59, 59);
        }

        return $ref->modify('last day of this month')->setTime(23, 59, 59);
    }

    /**
     * Helper for subclasses: period key used to group one invoice per window.
     */
    protected function periodKey(PeopleLink $link, DateTimeInterface $referenceDate): string
    {
        $start = $this->resolvePeriodStart($link, $referenceDate);
        $end = $this->resolvePeriodEnd($link, $referenceDate);

        return sprintf(
            '%s:%s:%s',
            $this->getInvoiceType(),
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );
    }
}
