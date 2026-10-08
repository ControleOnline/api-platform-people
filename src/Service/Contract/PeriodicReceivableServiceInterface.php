<?php

declare(strict_types=1);

namespace ControleOnline\Service\Contract;

use ControleOnline\Entity\PeopleLink;
use DateTimeInterface;

/**
 * Common contract for period-based receivable/payable aggregation
 * (commission invoices for salespeople, royalty invoices for franchises).
 *
 * Invoice entity lives in api-platform-financial; callers pass the concrete
 * Invoice instance. Order linkage is intentionally untyped so this package
 * stays free of a hard dependency on api-platform-orders.
 *
 * @see docs/technical/PeriodicReceivableServices.md
 */
interface PeriodicReceivableServiceInterface
{
    /**
     * Whether this service should generate a movement for the given source invoice.
     *
     * @param object $sourceInvoice api-platform-financial Invoice (or compatible)
     */
    public function supports(object $sourceInvoice): bool;

    /**
     * Locate the open aggregate invoice for the link + closing period, or create one.
     *
     * @return object Invoice (api-platform-financial)
     */
    public function resolveOpenInvoice(PeopleLink $link, DateTimeInterface $referenceDate): object;

    /**
     * Attach an order (or source document) to the aggregate invoice for tracing.
     *
     * @param object $aggregateInvoice
     * @param object $order            Order entity or compatible identifier carrier
     */
    public function attachOrder(object $aggregateInvoice, object $order): void;

    /**
     * Compute amount from base value applying link commission/minimum and
     * client-level override when present.
     *
     * @param float       $baseAmount   order/source amount
     * @param PeopleLink  $link         salesman or franchise link with rates
     * @param PeopleLink|null $clientOverride optional client-specific override link
     */
    public function computeAmount(float $baseAmount, PeopleLink $link, ?PeopleLink $clientOverride = null): float;

    /**
     * Due date = end of closing period for $referenceDate + payment_term_days.
     */
    public function resolveDueDate(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface;

    /**
     * Inclusive start of the closing period that contains $referenceDate.
     */
    public function resolvePeriodStart(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface;

    /**
     * Inclusive end of the closing period that contains $referenceDate.
     */
    public function resolvePeriodEnd(PeopleLink $link, DateTimeInterface $referenceDate): DateTimeInterface;

    /**
     * Invoice type discriminator used by this service (e.g. "comission", "royalties").
     */
    public function getInvoiceType(): string;
}
