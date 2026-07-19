<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\MediaType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use ControleOnline\Service\FileService;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PeopleMediaUploadController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FileService $fileService,
        private HydratorService $hydratorService,
        private PeopleService $peopleService,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $uploadedFile = $request->files->get('file');
            $isHuman = $this->authorizationChecker->isGranted('ROLE_HUMAN');
            $authenticatedPeople = $this->peopleService->getMyPeople();
            if (!$authenticatedPeople instanceof People) {
                throw new AccessDeniedHttpException('authenticated person not found');
            }

            $peopleReference = $request->request->get('people');
            $mediaTypeReference =
                $request->request->get('media_type_id')
                ?: $request->request->get('mediaType')
                ?: $request->request->get('media_type');

            if (!$uploadedFile) {
                throw new BadRequestHttpException('file is required');
            }

            $people = $isHuman && $peopleReference
                ? $this->fileService->resolvePeopleReference($peopleReference)
                : $authenticatedPeople;
            if (!$people) {
                throw new BadRequestHttpException('people not found');
            }

            $mediaTypeId = $isHuman
                ? (int) preg_replace('/\D+/', '', (string) $mediaTypeReference)
                : 0;
            $mediaType = $mediaTypeId > 0
                ? $this->manager->getRepository(MediaType::class)->find($mediaTypeId)
                : $this->manager->getRepository(MediaType::class)->findOneBy([
                    'type' => strtoupper((string) $people->getPeopleType()) === 'F' ? 'avatar' : 'logo',
                    'peopleType' => strtoupper((string) $people->getPeopleType()),
                ]);
            if (!$mediaType instanceof MediaType) {
                throw new BadRequestHttpException('media type not found');
            }

            if (!$isHuman && strtolower((string) $mediaType->getType()) !== 'avatar') {
                throw new AccessDeniedHttpException('clients can only update their own avatar');
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
                $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
