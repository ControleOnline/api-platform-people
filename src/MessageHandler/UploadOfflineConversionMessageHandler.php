<?php

declare(strict_types=1);

namespace ControleOnline\MessageHandler;

use ControleOnline\Message\UploadOfflineConversionMessage;
use ControleOnline\Service\LoggerService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Posts a single offline conversion to api-platform-integration. Idempotency
 * is enforced by caching the processed (idempotency_key) for 24h so a retried
 * message or a status flapping between paid/other does not double-count.
 */
#[AsMessageHandler]
final class UploadOfflineConversionMessageHandler
{
    private const IDEMPOTENCY_TTL = 86400;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerService $logger,
    ) {
    }

    public function __invoke(UploadOfflineConversionMessage $message): void
    {
        $item = $this->cache->getItem('google-ads.offline-conv.' . sha1($message->idempotencyKey));
        if ($item->isHit()) {
            return;
        }

        $baseUrl = rtrim((string) ($_ENV['INTEGRATION_INTERNAL_BASE_URL'] ?? ''), '/');
        if ($baseUrl === '') {
            $this->logger->info('offline-conversion.skipped', ['reason' => 'missing_base_url', 'order_id' => $message->orderId]);
            return;
        }

        try {
            $response = $this->httpClient->request('POST', $baseUrl . '/marketing/ads/conversions/upload', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'people' => '/people/' . $message->companyId,
                    'conversions' => [[
                        'gclid' => $message->gclid,
                        'conversionAction' => $message->conversionAction,
                        'conversionDateTime' => $message->conversionDateTime,
                        'conversionValue' => $message->conversionValue,
                        'currencyCode' => $message->currencyCode,
                    ]],
                ], JSON_THROW_ON_ERROR),
            ]);
            $code = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (HttpClientException $e) {
            $this->logger->warning('offline-conversion.http_failed', [
                'order_id' => $message->orderId,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($code >= 400) {
            $this->logger->warning('offline-conversion.rejected', [
                'order_id' => $message->orderId,
                'status' => $code,
                'body' => substr($body, 0, 500),
            ]);
            return;
        }

        $item->set(['uploaded_at' => gmdate('c')]);
        $item->expiresAfter(self::IDEMPOTENCY_TTL);
        $this->cache->save($item);

        $this->logger->info('offline-conversion.uploaded', [
            'order_id' => $message->orderId,
            'idempotency_key' => $message->idempotencyKey,
        ]);
    }
}
