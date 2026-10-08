<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleLink;
use DateTimeInterface;
use RuntimeException;

/**
 * Aggregates salesperson commission invoices (invoice type "comission").
 *
 * Perspective: salesman receives from the company.
 * One open invoice per PeopleLink + closing period.
 *
 * Persistence hooks are injectable so unit tests do not need Doctrine.
 */
class CommissionService extends AbstractPeriodicReceivableService
{
    public const INVOICE_TYPE = 'comission';

    /**
     * @param callable|null $invoiceResolver fn(PeopleLink, DateTimeInterface, string $type): object
     * @param callable|null $orderAttacher   fn(object $invoice, object $order): void
     * @param callable|null $sourceTypeReader fn(object $sourceInvoice): ?string
     */
    public function __construct(
        private $invoiceResolver = null,
        private $orderAttacher = null,
        private $sourceTypeReader = null,
    ) {
    }

    public function getInvoiceType(): string
    {
        return self::INVOICE_TYPE;
    }

    public function supports(object $sourceInvoice): bool
    {
        if ($this->sourceTypeReader !== null) {
            $type = ($this->sourceTypeReader)($sourceInvoice);

            return $type === null || $type === 'order' || $type === 'sale';
        }

        // Default: support any object that looks like a commercial sale source
        return true;
    }

    public function resolveOpenInvoice(PeopleLink $link, DateTimeInterface $referenceDate): object
    {
        if ($this->invoiceResolver === null) {
            throw new RuntimeException(
                'CommissionService requires an invoiceResolver callable to locate/create aggregate invoices. '
                . 'Wire it from api-platform-financial / api-community composition root.'
            );
        }

        return ($this->invoiceResolver)($link, $referenceDate, self::INVOICE_TYPE);
    }

    public function attachOrder(object $aggregateInvoice, object $order): void
    {
        if ($this->orderAttacher === null) {
            throw new RuntimeException(
                'CommissionService requires an orderAttacher callable to link orders to invoices.'
            );
        }

        ($this->orderAttacher)($aggregateInvoice, $order);
    }
}
