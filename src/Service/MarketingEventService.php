<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\MarketingEvent;
use ControleOnline\Entity\People;
use ControleOnline\Repository\MarketingEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MarketingEventService
{
    private const MAX_URL_LEN = 2048;
    private const MAX_UTM_LEN = 255;
    private const MAX_VISITOR_LEN = 64;

    public function __construct(
        private EntityManagerInterface $em,
        private MarketingEventRepository $repository,
    ) {}

    /**
     * Persist a public marketing event. Idempotent on (visitor_id, event_name, timestamp).
     *
     * @param array<string, mixed> $payload
     */
    public function createFromPayload(array $payload): MarketingEvent
    {
        $eventName = $this->requireString($payload, 'event_name', 64);
        if (!in_array($eventName, MarketingEvent::ALLOWED_EVENTS, true)) {
            throw new BadRequestHttpException(
                'event_name must be one of: ' . implode(', ', MarketingEvent::ALLOWED_EVENTS)
            );
        }

        $visitorId = $this->requireString($payload, 'visitor_id', self::MAX_VISITOR_LEN);
        if ($visitorId === '') {
            throw new BadRequestHttpException('visitor_id is required');
        }

        $eventAt = $this->parseEventAt($payload['timestamp'] ?? null);
        $idempotencyKey = $this->buildIdempotencyKey($visitorId, $eventName, $eventAt, $payload);

        $existing = $this->repository->findOneByIdempotencyKey($idempotencyKey);
        if ($existing instanceof MarketingEvent) {
            return $existing;
        }

        $event = new MarketingEvent();
        $event->setEventName($eventName);
        $event->setEventAt($eventAt);
        $event->setVisitorId($visitorId);
        $event->setIdempotencyKey($idempotencyKey);
        $event->setPageUrl($this->optionalString($payload, 'page_url', self::MAX_URL_LEN));
        $event->setLeadId($this->optionalString($payload, 'lead_id', 64));
        $event->setUtmSource($this->optionalString($payload, 'utm_source', self::MAX_UTM_LEN));
        $event->setUtmMedium($this->optionalString($payload, 'utm_medium', self::MAX_UTM_LEN));
        $event->setUtmCampaign($this->optionalString($payload, 'utm_campaign', self::MAX_UTM_LEN));
        $event->setUtmTerm($this->optionalString($payload, 'utm_term', self::MAX_UTM_LEN));
        $event->setUtmContent($this->optionalString($payload, 'utm_content', self::MAX_UTM_LEN));
        $event->setReferrer($this->optionalString($payload, 'referrer', self::MAX_URL_LEN));
        $event->setEmail($this->normalizeEmail($payload['email'] ?? null));
        $event->setPayloadHash($this->hashPayload($payload));

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    /**
     * When create-account receives marketing.visitor_id, link prior events to the new People.
     */
    public function associateVisitorToPeople(string $visitorId, People $people): int
    {
        $visitorId = trim($visitorId);
        if ($visitorId === '' || strlen($visitorId) > self::MAX_VISITOR_LEN) {
            return 0;
        }

        return $this->repository->linkVisitorToPeople($visitorId, $people);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildIdempotencyKey(
        string $visitorId,
        string $eventName,
        \DateTimeImmutable $eventAt,
        array $payload
    ): string {
        $clientKey = isset($payload['idempotency_key'])
            ? trim((string) $payload['idempotency_key'])
            : '';
        if ($clientKey !== '' && strlen($clientKey) <= 64) {
            return hash('sha256', $clientKey);
        }

        $raw = $visitorId . '|' . $eventName . '|' . $eventAt->format(\DateTimeInterface::ATOM);
        return hash('sha256', $raw);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hashPayload(array $payload): string
    {
        $normalized = $payload;
        ksort($normalized);
        return hash('sha256', (string) json_encode($normalized));
    }

    private function parseEventAt(mixed $timestamp): \DateTimeImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return new \DateTimeImmutable();
        }
        if (!is_string($timestamp) && !is_numeric($timestamp)) {
            throw new BadRequestHttpException('timestamp must be ISO-8601 string');
        }
        try {
            return new \DateTimeImmutable(is_numeric($timestamp) ? '@' . $timestamp : (string) $timestamp);
        } catch (\Exception) {
            throw new BadRequestHttpException('timestamp must be a valid ISO-8601 datetime');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $key, int $maxLen): string
    {
        if (!isset($payload[$key]) || !is_scalar($payload[$key])) {
            throw new BadRequestHttpException($key . ' is required');
        }
        $value = trim((string) $payload[$key]);
        if (strlen($value) > $maxLen) {
            throw new BadRequestHttpException($key . ' exceeds max length ' . $maxLen);
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalString(array $payload, string $key, int $maxLen): ?string
    {
        if (!isset($payload[$key]) || $payload[$key] === null || $payload[$key] === '') {
            return null;
        }
        if (!is_scalar($payload[$key])) {
            throw new BadRequestHttpException($key . ' must be a string');
        }
        $value = trim((string) $payload[$key]);
        if (strlen($value) > $maxLen) {
            $value = substr($value, 0, $maxLen);
        }
        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }
        if (!is_scalar($email)) {
            return null;
        }
        $email = strtolower(trim((string) $email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return substr($email, 0, 255);
    }
}
