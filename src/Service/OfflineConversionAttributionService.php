<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\MarketingEvent;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Message\UploadOfflineConversionMessage;
use ControleOnline\Repository\MarketingEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * When an Order closes paid, find the lead_created MarketingEvent that owns the
 * client's gclid (within the last 90 days) and queue the offline conversion
 * upload. Keeps attribution independent from the Order service so fixing one
 * side does not reshape the other.
 */
final class OfflineConversionAttributionService
{
    public const LOOKBACK_DAYS = 90;
    public const CONFIG_CONVERSION_ACTION = 'GOOGLE_ADS_CONVERSION_ACTION';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarketingEventRepository $marketingEventRepository,
        private readonly ConfigService $configService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Returns true when a conversion upload was scheduled.
     */
    public function attributeOrder(Order $order): bool
    {
        $client = $order->getClient();
        if (!$client instanceof People) {
            return false;
        }

        $event = $this->findGclidEvent($client);
        if ($event === null) {
            return false;
        }

        $company = $this->companyForUpload($order);
        if (!$company instanceof People) {
            return false;
        }

        $conversionAction = $this->resolveConversionAction($company);
        if ($conversionAction === '') {
            return false;
        }

        $this->messageBus->dispatch(new UploadOfflineConversionMessage(
            orderId: (int) $order->getId(),
            companyId: (int) $company->getId(),
            gclid: (string) $event->getGclid(),
            conversionAction: $conversionAction,
            conversionDateTime: $this->orderPaidAt($order),
            conversionValue: (float) ($order->getPrice() ?? 0),
            currencyCode: $this->orderCurrency($order),
            idempotencyKey: sprintf('order-%d-%s', $order->getId(), $conversionAction),
        ));

        return true;
    }

    private function findGclidEvent(People $client): ?MarketingEvent
    {
        $since = new \DateTimeImmutable('-' . self::LOOKBACK_DAYS . ' days');
        $qb = $this->marketingEventRepository->createQueryBuilder('e')
            ->andWhere('e.people = :people')
            ->andWhere('e.eventName = :event')
            ->andWhere('e.gclid IS NOT NULL')
            ->andWhere("e.gclid != ''")
            ->andWhere('e.eventAt >= :since')
            ->orderBy('e.eventAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('people', $client)
            ->setParameter('event', MarketingEvent::EVENT_LEAD_CREATED)
            ->setParameter('since', $since);

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result instanceof MarketingEvent ? $result : null;
    }

    private function companyForUpload(Order $order): ?People
    {
        $provider = $order->getProvider();
        return $provider instanceof People ? $provider : null;
    }

    private function resolveConversionAction(People $company): string
    {
        $raw = $this->configService->getConfig($company, self::CONFIG_CONVERSION_ACTION);
        return is_string($raw) ? trim($raw) : '';
    }

    private function orderPaidAt(Order $order): string
    {
        if (method_exists($order, 'getAlterDate') && $order->getAlterDate() instanceof \DateTimeInterface) {
            return $order->getAlterDate()->format('Y-m-d H:i:s O');
        }
        return gmdate('Y-m-d H:i:s O');
    }

    private function orderCurrency(Order $order): string
    {
        if (method_exists($order, 'getCurrency')) {
            $currency = $order->getCurrency();
            if (is_string($currency) && $currency !== '') {
                return strtoupper($currency);
            }
        }
        return 'BRL';
    }
}
