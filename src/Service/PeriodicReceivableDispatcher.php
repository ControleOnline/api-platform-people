<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\Contract\PeriodicReceivableServiceInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orchestrates commission / royalty aggregation when a source invoice enters the system.
 *
 * Intended call site: InvoiceService::postPersist (or equivalent create path) in
 * api-platform-financial / api-community — after the commercial invoice is persisted.
 *
 * This class stays free of hard Doctrine/financial dependencies: link resolution,
 * amount extraction and order extraction are injectable callables.
 *
 * @see docs/technical/PeriodicReceivableDispatcher.md
 * @see docs/technical/PeriodicReceivableServices.md
 */
class PeriodicReceivableDispatcher
{
    /** @var list<PeriodicReceivableServiceInterface> */
    private array $services;

    private LoggerInterface $logger;

    /**
     * @param iterable<PeriodicReceivableServiceInterface> $services
     * @param callable|null $linkResolver fn(object $sourceInvoice, string $role): ?PeopleLink
     *        role examples: 'salesman' | 'franchisee' | 'courier'
     * @param callable|null $amountReader fn(object $sourceInvoice): float
     * @param callable|null $orderReader  fn(object $sourceInvoice): ?object  Order or null
     * @param callable|null $referenceDateReader fn(object $sourceInvoice): DateTimeInterface
     * @param callable|null $clientOverrideResolver fn(object $sourceInvoice, PeopleLink $link): ?PeopleLink
     */
    public function __construct(
        iterable $services = [],
        private $linkResolver = null,
        private $amountReader = null,
        private $orderReader = null,
        private $referenceDateReader = null,
        private $clientOverrideResolver = null,
        ?LoggerInterface $logger = null,
    ) {
        $list = [];
        foreach ($services as $service) {
            if ($service instanceof PeriodicReceivableServiceInterface) {
                $list[] = $service;
            }
        }
        if ($list === []) {
            $list = [new CommissionService(), new RoyaltiesService()];
        }
        $this->services = $list;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Entry point: call after a commercial source invoice is created/persisted.
     *
     * @param object $sourceInvoice Invoice (or compatible) from financial
     * @return list<object> aggregate invoices touched (may be empty)
     */
    public function dispatch(object $sourceInvoice): array
    {
        $touched = [];
        $refDate = $this->resolveReferenceDate($sourceInvoice);
        $baseAmount = $this->resolveAmount($sourceInvoice);
        $order = $this->resolveOrder($sourceInvoice);

        foreach ($this->services as $service) {
            if (!$service->supports($sourceInvoice)) {
                continue;
            }

            $link = $this->resolveLinkForService($sourceInvoice, $service);
            if ($link === null) {
                $this->logger->debug('PeriodicReceivableDispatcher: no PeopleLink for {type}', [
                    'type' => $service->getInvoiceType(),
                ]);
                continue;
            }

            try {
                $aggregate = $service->resolveOpenInvoice($link, $refDate);
                $override = $this->resolveClientOverride($sourceInvoice, $link);
                $amount = $service->computeAmount($baseAmount, $link, $override);

                // Caller-supplied invoiceResolver is expected to have set price/due date
                // when creating; here we only attach order for tracing when available.
                if ($order !== null) {
                    $service->attachOrder($aggregate, $order);
                }

                $touched[] = $aggregate;

                $this->logger->info('PeriodicReceivableDispatcher: aggregated {type} amount {amount}', [
                    'type' => $service->getInvoiceType(),
                    'amount' => $amount,
                    'link_id' => method_exists($link, 'getId') ? $link->getId() : null,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('PeriodicReceivableDispatcher failed for {type}: {message}', [
                    'type' => $service->getInvoiceType(),
                    'message' => $e->getMessage(),
                ]);
                // Fail-open: one service failure must not block the source invoice path.
            }
        }

        return $touched;
    }

    private function resolveReferenceDate(object $sourceInvoice): DateTimeInterface
    {
        if ($this->referenceDateReader !== null) {
            return ($this->referenceDateReader)($sourceInvoice);
        }

        if (method_exists($sourceInvoice, 'getIssueDate') && $sourceInvoice->getIssueDate() instanceof DateTimeInterface) {
            return $sourceInvoice->getIssueDate();
        }
        if (method_exists($sourceInvoice, 'getDueDate') && $sourceInvoice->getDueDate() instanceof DateTimeInterface) {
            return $sourceInvoice->getDueDate();
        }
        if (method_exists($sourceInvoice, 'getCreatedAt') && $sourceInvoice->getCreatedAt() instanceof DateTimeInterface) {
            return $sourceInvoice->getCreatedAt();
        }

        return new DateTimeImmutable('now');
    }

    private function resolveAmount(object $sourceInvoice): float
    {
        if ($this->amountReader !== null) {
            return (float) ($this->amountReader)($sourceInvoice);
        }
        if (method_exists($sourceInvoice, 'getPrice')) {
            return (float) $sourceInvoice->getPrice();
        }
        if (method_exists($sourceInvoice, 'getTotal')) {
            return (float) $sourceInvoice->getTotal();
        }

        return 0.0;
    }

    private function resolveOrder(object $sourceInvoice): ?object
    {
        if ($this->orderReader !== null) {
            return ($this->orderReader)($sourceInvoice);
        }
        if (method_exists($sourceInvoice, 'getOrder')) {
            $orders = $sourceInvoice->getOrder();
            if (is_iterable($orders)) {
                foreach ($orders as $link) {
                    if (is_object($link) && method_exists($link, 'getOrder')) {
                        $o = $link->getOrder();
                        if (is_object($o)) {
                            return $o;
                        }
                    }
                    if (is_object($link)) {
                        return $link;
                    }
                }
            }
        }

        return null;
    }

    private function resolveLinkForService(
        object $sourceInvoice,
        PeriodicReceivableServiceInterface $service
    ): ?PeopleLink {
        $role = match ($service->getInvoiceType()) {
            CommissionService::INVOICE_TYPE => 'salesman',
            RoyaltiesService::INVOICE_TYPE => 'franchisee',
            default => $service->getInvoiceType(),
        };

        if ($this->linkResolver !== null) {
            $link = ($this->linkResolver)($sourceInvoice, $role);

            return $link instanceof PeopleLink ? $link : null;
        }

        return null;
    }

    private function resolveClientOverride(object $sourceInvoice, PeopleLink $link): ?PeopleLink
    {
        if ($this->clientOverrideResolver !== null) {
            $override = ($this->clientOverrideResolver)($sourceInvoice, $link);

            return $override instanceof PeopleLink ? $override : null;
        }

        return null;
    }
}
