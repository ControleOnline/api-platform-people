<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\MarketingEventService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Public POST /marketing_events — site/plugin conversion tracking (no auth).
 */
class CreateMarketingEventAction
{
    public function __construct(
        private MarketingEventService $marketingEventService,
        private HydratorService $hydratorService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            if ($content === null || $content === '') {
                throw new BadRequestHttpException('JSON body is required');
            }

            $payload = json_decode($content, true);
            if (!is_array($payload)) {
                throw new BadRequestHttpException('Invalid JSON body');
            }

            $event = $this->marketingEventService->createFromPayload($payload);

            return new JsonResponse([
                'id' => $event->getId(),
                'event_name' => $event->getEventName(),
                'visitor_id' => $event->getVisitorId(),
                'event_at' => $event->getEventAt()->format(\DateTimeInterface::ATOM),
                'idempotency_key' => $event->getIdempotencyKey(),
            ], 201);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                500
            );
        }
    }
}
