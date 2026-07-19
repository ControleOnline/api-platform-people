<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\PeopleMediaUploadController;
use ControleOnline\Entity\File;
use ControleOnline\Entity\MediaType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use ControleOnline\Service\FileService;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PeopleMediaUploadControllerTest extends TestCase
{
    public function testClientUploadUsesAuthenticatedPeopleAndInternalAvatarType(): void
    {
        $people = (new People())->setPeopleType('F');
        $mediaType = (new MediaType())->setType('avatar')->setPeopleType('F');
        $file = $this->createStub(File::class);

        $mediaTypeRepository = $this->createMock(EntityRepository::class);
        $mediaTypeRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['type' => 'avatar', 'peopleType' => 'F'])
            ->willReturn($mediaType);
        $mediaTypeRepository->expects(self::never())->method('find');

        $peopleMediaRepository = $this->createMock(EntityRepository::class);
        $peopleMediaRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['people' => $people, 'mediaType' => $mediaType], ['id' => 'ASC'])
            ->willReturn(null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(
            static fn(string $class) => $class === MediaType::class
                ? $mediaTypeRepository
                : $peopleMediaRepository
        );
        $manager->expects(self::once())->method('persist')->with(self::isInstanceOf(PeopleMedia::class));
        $manager->expects(self::once())->method('flush');

        $fileService = $this->createMock(FileService::class);
        $fileService->expects(self::never())->method('resolvePeopleReference');
        $fileService->expects(self::once())
            ->method('addFile')
            ->with($people, self::isString(), 'people_media', 'avatar.png', 'image', 'png')
            ->willReturn($file);

        $hydrator = $this->createMock(HydratorService::class);
        $hydrator->expects(self::once())
            ->method('data')
            ->with(self::isInstanceOf(PeopleMedia::class), 'people_media:read')
            ->willReturn(['file' => ['id' => 77]]);

        $peopleService = $this->createStub(PeopleService::class);
        $peopleService->method('getMyPeople')->willReturn($people);

        $authorization = $this->createStub(AuthorizationCheckerInterface::class);
        $authorization->method('isGranted')->willReturn(false);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'avatar-test-');
        self::assertNotFalse($temporaryFile);
        file_put_contents(
            $temporaryFile,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );

        try {
            $request = new Request(
                ['people' => '/people/999', 'media_type_id' => '999'],
                [],
                [],
                [],
                ['file' => new UploadedFile($temporaryFile, 'avatar.png', 'image/png', null, true)]
            );

            $response = (new PeopleMediaUploadController(
                $manager,
                $fileService,
                $hydrator,
                $peopleService,
                $authorization
            ))($request);

            self::assertSame(201, $response->getStatusCode());
            self::assertSame(['file' => ['id' => 77]], json_decode($response->getContent(), true));
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }
}
