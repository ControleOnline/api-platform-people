<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleAccessEvent;
use ControleOnline\Entity\PeopleExportJob;
use ControleOnline\Repository\PeopleAccessEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PeopleExportJobService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FileService $fileService,
        private PeopleAccessEventRepository $peopleAccessEventRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function generateTimesheetExport(array $payload): PeopleExportJob
    {
        $context = $this->normalizeText($payload['context'] ?? PeopleExportJob::CONTEXT_EMPLOYMENT)
            ?: PeopleExportJob::CONTEXT_EMPLOYMENT;
        $kind = $this->normalizeText($payload['kind'] ?? PeopleExportJob::KIND_TIMESHEET)
            ?: PeopleExportJob::KIND_TIMESHEET;

        if ($kind !== PeopleExportJob::KIND_TIMESHEET) {
            throw new Exception('Unsupported export kind');
        }

        $company = $this->fileService->resolvePeopleReference(
            $payload['company'] ?? $payload['companyId'] ?? null
        );
        if (!$company instanceof People) {
            throw new Exception('company is required');
        }

        $peopleReference = $payload['people'] ?? $payload['peopleId'] ?? null;
        $people = $peopleReference !== null
            ? $this->fileService->resolvePeopleReference($peopleReference)
            : null;

        $periodStart = $this->normalizeDateValue($payload['periodStart'] ?? $payload['period_start'] ?? null);
        $periodEnd = $this->normalizeDateValue($payload['periodEnd'] ?? $payload['period_end'] ?? null);

        if (!$periodStart instanceof \DateTimeInterface || !$periodEnd instanceof \DateTimeInterface) {
            throw new Exception('periodStart and periodEnd are required');
        }

        if ($periodStart > $periodEnd) {
            throw new Exception('periodStart must be before periodEnd');
        }

        $job = new PeopleExportJob();
        $job->setContext($context);
        $job->setKind($kind);
        $job->setCompany($company);
        $job->setPeople($people);
        $job->setPeriodStart($periodStart);
        $job->setPeriodEnd($periodEnd);
        $job->setStatus(PeopleExportJob::STATUS_PROCESSING);
        $job->setFilters($this->normalizeFilters($payload));

        $this->manager->persist($job);
        $this->manager->flush();

        try {
            $events = $this->peopleAccessEventRepository->findTimesheetEvents(
                $company,
                $people,
                $this->toRangeStart($periodStart),
                $this->toRangeEnd($periodEnd),
                $context
            );

            $csvContent = $this->buildTimesheetCsv($events, $company, $people, $context, $periodStart, $periodEnd);
            $fileName = $this->buildFileName($company, $periodStart, $periodEnd);
            $file = $this->fileService->addFile(
                $company,
                $csvContent,
                'people_export_jobs',
                $fileName,
                'text',
                'csv'
            );

            $job->setFile($file);
            $job->setStatus(PeopleExportJob::STATUS_DONE);
            $job->setFinishedAt(new \DateTime('now'));
            $this->manager->persist($job);
            $this->manager->flush();

            return $job;
        } catch (Exception $e) {
            $job->setStatus(PeopleExportJob::STATUS_ERROR);
            $job->setErrorMessage($e->getMessage());
            $job->setFinishedAt(new \DateTime('now'));
            $this->manager->persist($job);
            $this->manager->flush();

            throw $e;
        }
    }

    /**
     * @param array<int, PeopleAccessEvent> $events
     */
    private function buildTimesheetCsv(
        array $events,
        People $company,
        ?People $people,
        string $context,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): string {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new Exception('Unable to open export stream');
        }

        fwrite($handle, "\xEF\xBB\xBF");

        $headers = [
            'context',
            'company_id',
            'company_name',
            'employee_id',
            'employee_name',
            'date',
            'entry_at',
            'exit_at',
            'worked_minutes',
            'worked_hours',
            'source',
        ];
        fputcsv($handle, $headers, ';');

        $openSessions = [];
        foreach ($events as $event) {
            if (!$event instanceof PeopleAccessEvent || !$event->getEventAt() instanceof \DateTimeInterface) {
                continue;
            }

            $eventPeople = $event->getPeople();
            if (!$eventPeople instanceof People) {
                continue;
            }

            $peopleKey = (int) $eventPeople->getId();
            $dateKey = $event->getEventAt()->format('Y-m-d');

            $openSessions[$peopleKey] ??= [];
            $openSessions[$peopleKey][$dateKey] ??= [];

            if ($event->getDirection() === PeopleAccessEvent::DIRECTION_ENTRY) {
                $openSessions[$peopleKey][$dateKey][] = $event->getEventAt();
                continue;
            }

            $entryAt = array_shift($openSessions[$peopleKey][$dateKey]);
            $workedMinutes = $entryAt instanceof \DateTimeInterface
                ? max(0, (int) floor(($event->getEventAt()->getTimestamp() - $entryAt->getTimestamp()) / 60))
                : null;

            fputcsv($handle, [
                $context,
                $company->getId(),
                $this->resolvePeopleLabel($company),
                $eventPeople->getId(),
                $this->resolvePeopleLabel($eventPeople),
                $dateKey,
                $entryAt instanceof \DateTimeInterface ? $entryAt->format('H:i:s') : '',
                $event->getEventAt()->format('H:i:s'),
                $workedMinutes,
                $workedMinutes !== null ? number_format($workedMinutes / 60, 2, '.', '') : '',
                $event->getSource(),
            ], ';');
        }

        foreach ($openSessions as $peopleKey => $dailySessions) {
            $peopleEntity = $this->resolvePeopleById((int) $peopleKey);
            if (!$peopleEntity instanceof People) {
                continue;
            }

            foreach ($dailySessions as $dateKey => $entries) {
                foreach ($entries as $entryAt) {
                    if (!$entryAt instanceof \DateTimeInterface) {
                        continue;
                    }

                    fputcsv($handle, [
                        $context,
                        $company->getId(),
                        $this->resolvePeopleLabel($company),
                        $peopleEntity->getId(),
                        $this->resolvePeopleLabel($peopleEntity),
                        $dateKey,
                        $entryAt->format('H:i:s'),
                        '',
                        '',
                        '',
                        'open-session',
                    ], ';');
                }
            }
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        if ($csvContent === false) {
            throw new Exception('Unable to read export stream');
        }

        return $csvContent;
    }

    private function buildFileName(People $company, \DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): string
    {
        $companySlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $this->resolvePeopleLabel($company)) ?: 'company');
        $range = sprintf('%s_%s', $periodStart->format('Ymd'), $periodEnd->format('Ymd'));

        return trim(sprintf('folha-ponto-%s-%s', $companySlug, $range), '-');
    }

    private function normalizeFilters(array $payload): array
    {
        return [
            'context' => $this->normalizeText($payload['context'] ?? PeopleExportJob::CONTEXT_EMPLOYMENT) ?: PeopleExportJob::CONTEXT_EMPLOYMENT,
            'kind' => $this->normalizeText($payload['kind'] ?? PeopleExportJob::KIND_TIMESHEET) ?: PeopleExportJob::KIND_TIMESHEET,
            'companyId' => $this->normalizeText($payload['company'] ?? $payload['companyId'] ?? ''),
            'peopleId' => $this->normalizeText($payload['people'] ?? $payload['peopleId'] ?? ''),
            'periodStart' => $this->normalizeDateValue($payload['periodStart'] ?? $payload['period_start'] ?? null)?->format('Y-m-d'),
            'periodEnd' => $this->normalizeDateValue($payload['periodEnd'] ?? $payload['period_end'] ?? null)?->format('Y-m-d'),
        ];
    }

    private function normalizeText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeDateValue(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Exception) {
            return null;
        }
    }

    private function toRangeStart(\DateTimeInterface $date): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    }

    private function toRangeEnd(\DateTimeInterface $date): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);
    }

    private function resolvePeopleById(int $id): ?People
    {
        if ($id <= 0) {
            return null;
        }

        return $this->manager->getRepository(People::class)->find($id);
    }

    private function resolvePeopleLabel(?People $people): string
    {
        if (!$people instanceof People) {
            return '';
        }

        $alias = trim((string) $people->getAlias());
        $name = trim((string) $people->getName());

        if ($alias !== '' && $name !== '' && $alias !== $name) {
            return sprintf('%s - %s', $alias, $name);
        }

        return $alias !== '' ? $alias : $name;
    }
}
