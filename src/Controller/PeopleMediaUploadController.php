<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\MediaType;
use ControleOnline\Entity\PeopleMedia;
use ControleOnline\Service\FileService;
use ControleOnline\Service\HydratorService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PeopleMediaUploadController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FileService $fileService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $uploadedFile = $request->files->get('file');
            $peopleReference = $request->request->get('people');
            $mediaTypeReference =
                $request->request->get('media_type_id')
                ?: $request->request->get('mediaType')
                ?: $request->request->get('media_type');

            if (!$uploadedFile) {
                throw new BadRequestHttpException('file is required');
            }

            $people = $this->fileService->resolvePeopleReference($peopleReference);
            if (!$people) {
                throw new BadRequestHttpException('people not found');
            }

            $mediaTypeId = (int) preg_replace('/\D+/', '', (string) $mediaTypeReference);
            if ($mediaTypeId <= 0) {
                throw new BadRequestHttpException('media_type_id is required');
            }

            $mediaType = $this->manager->getRepository(MediaType::class)->find($mediaTypeId);
            if (!$mediaType instanceof MediaType) {
                throw new BadRequestHttpException('media type not found');
            }

            if (strtoupper((string) $people->getPeopleType()) !== $mediaType->getPeopleType()) {
                throw new BadRequestHttpException('media type does not match people type');
            }

            $originalExtension = strtolower((string) $uploadedFile->getClientOriginalExtension());
            $clientMimeType = strtolower((string) $uploadedFile->getClientMimeType());
            $detectedMimeType = strtolower((string) $uploadedFile->getMimeType());

            $isPngFile =
                $originalExtension === 'png'
                && ($clientMimeType === 'image/png' || $detectedMimeType === 'image/png');

            if (!$isPngFile) {
                throw new BadRequestHttpException('only png images are allowed');
            }

            $content = file_get_contents($uploadedFile->getPathname());
            if ($content === false) {
                throw new BadRequestHttpException('failed to read uploaded file');
            }

            $fileEntity = $this->fileService->addFile(
                $people,
                $content,
                'people_media',
                $uploadedFile->getClientOriginalName(),
                'image',
                'png'
            );

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

            $peopleMedia->setFile($fileEntity);

            $this->manager->persist($peopleMedia);
            $this->manager->flush();

            return new JsonResponse(
                $this->hydratorService->data($peopleMedia, 'people_media:read'),
                $isNewAssociation ? Response::HTTP_CREATED : Response::HTTP_OK
            );
        } catch (Exception $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                $e instanceof BadRequestHttpException ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
