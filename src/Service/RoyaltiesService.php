<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleLink;
use DateTimeInterface;
use RuntimeException;

/**
 * Aggregates franchise royalty invoices (invoice type "royalties").
 *
 * Perspective: franchisee pays franchisor.
 * One open invoice per PeopleLink + closing period.
 */
class RoyaltiesService extends AbstractPeriodicReceivableService
{
    public const INVOICE_TYPE = 'royalties';

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

            return $type === null || $type === 'order' || $type === 'sale' || $type === 'royalty_source';
        }

        return true;
    }

    public function resolveOpenInvoice(PeopleLink $link, DateTimeInterface $referenceDate): object
    {
        if ($this->invoiceResolver === null) {
            throw new RuntimeException(
                'RoyaltiesService requires an invoiceResolver callable to locate/create aggregate invoices. '
                . 'Wire it from api-platform-financial / api-community composition root.'
            );
        }

        return ($this->invoiceResolver)($link, $referenceDate, self::INVOICE_TYPE);
    }

    public function attachOrder(object $aggregateInvoice, object $order): void
    {
        if ($this->orderAttacher === null) {
            throw new RuntimeException(
                'RoyaltiesService requires an orderAttacher callable to link orders to invoices.'
            );
        }

        ($this->orderAttacher)($aggregateInvoice, $order);
    }
}
