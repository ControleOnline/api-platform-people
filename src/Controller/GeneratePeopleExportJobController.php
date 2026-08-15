<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PeopleExportJobService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeneratePeopleExportJobController
{
    public function __construct(
        private HydratorService $hydratorService,
        private PeopleExportJobService $peopleExportJobService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $payload = [];
            try {
                $payload = $request->toArray();
            } catch (Exception) {
                $payload = $request->request->all();
            }

            $job = $this->peopleExportJobService->generateTimesheetExport(
                is_array($payload) ? $payload : []
            );

            return new JsonResponse(
                $this->hydratorService->data($job, 'people_export_job:read'),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
