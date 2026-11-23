<?php

declare(strict_types=1);

namespace ControleOnline\Message;

/**
 * Async envelope for uploading one offline conversion to Google Ads
 * (consumed by UploadOfflineConversionMessageHandler, which calls
 * POST /marketing/ads/conversions/upload in api-platform-integration).
 */
final class UploadOfflineConversionMessage
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $companyId,
        public readonly string $gclid,
        public readonly string $conversionAction,
        public readonly string $conversionDateTime,
        public readonly float $conversionValue,
        public readonly string $currencyCode,
        public readonly string $idempotencyKey,
    ) {
    }
}
