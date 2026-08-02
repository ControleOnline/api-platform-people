<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\File;
use ControleOnline\Entity\MediaType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use ControleOnline\Service\FileService;
use ControleOnline\Service\HydratorService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PeopleMediaSaveController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FileService $fileService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $payload = json_decode((string) $request->getContent(), true);
            if (!is_array($payload)) {
                $payload = $request->request->all();
            }

            $people = $this->fileService->resolvePeopleReference($payload['people'] ?? null);
            if (!$people instanceof People) {
                throw new BadRequestHttpException('people not found');
            }

            $mediaType = $this->resolveMediaType($payload['mediaType'] ?? $payload['media_type'] ?? null);
            if (!$mediaType instanceof MediaType) {
                throw new BadRequestHttpException('media type not found');
            }

            $file = $this->resolveFile($payload['file'] ?? null);
            if (!$file instanceof File) {
                throw new BadRequestHttpException('file not found');
            }

            if (strtoupper((string) $people->getPeopleType()) !== $mediaType->getPeopleType()) {
                throw new BadRequestHttpException('media type does not match people type');
            }

            $peopleMedia = $this->manager
                ->getRepository(PeopleMedia::class)
                ->findOneBy(
                    ['people' => $people, 'mediaType' => $mediaType],
                    ['id' => 'ASC']
                );

            $isNewAssociation = !$peopleMedia instanceof PeopleMedia;
            if ($isNewAssociation) {
                $peopleMedia = new PeopleMedia();
                $peopleMedia->setPeople($people);
                $peopleMedia->setMediaType($mediaType);
            }

            $peopleMedia->setFile($file);

            $this->manager->persist($peopleMedia);
            $this->manager->flush();

            return new JsonResponse(
                $this->hydratorService->data($peopleMedia, 'people_media:read'),
                $isNewAssociation ? Response::HTTP_CREATED : Response::HTTP_OK
            );
        } catch (Exception $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function resolveMediaType(mixed $reference): ?MediaType
    {
        $mediaTypeId = (int) preg_replace('/\D+/', '', (string) $reference);
        if ($mediaTypeId <= 0) {
            return null;
        }

        return $this->manager->getRepository(MediaType::class)->find($mediaTypeId);
    }

    private function resolveFile(mixed $reference): ?File
    {
        $fileId = (int) preg_replace('/\D+/', '', (string) $reference);
        if ($fileId <= 0) {
            return null;
        }

        return $this->manager->getRepository(File::class)->find($fileId);
    }
}
