<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\PeopleMediaSaveController;
use ControleOnline\Entity\File;
use ControleOnline\Entity\MediaType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use ControleOnline\Service\FileService;
use ControleOnline\Service\HydratorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PeopleMediaSaveControllerTest extends TestCase
{
    public function testPostCreatesPeopleMediaFromExistingFile(): void
    {
        $people = (new People())->setPeopleType('J');
        $mediaType = (new MediaType())->setType('stamp')->setPeopleType('J');
        $file = $this->createStub(File::class);

        $peopleMediaRepository = $this->createMock(EntityRepository::class);
        $peopleMediaRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['people' => $people, 'mediaType' => $mediaType], ['id' => 'ASC'])
            ->willReturn(null);

        $mediaTypeRepository = $this->createMock(EntityRepository::class);
        $mediaTypeRepository->expects(self::once())->method('find')->with(12)->willReturn($mediaType);

        $fileRepository = $this->createMock(EntityRepository::class);
        $fileRepository->expects(self::once())->method('find')->with(77)->willReturn($file);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(
            static fn(string $class) => match ($class) {
                PeopleMedia::class => $peopleMediaRepository,
                MediaType::class => $mediaTypeRepository,
                File::class => $fileRepository,
                default => throw new \RuntimeException($class),
            }
        );
        $manager->expects(self::once())->method('persist')->with(self::isInstanceOf(PeopleMedia::class));
        $manager->expects(self::once())->method('flush');

        $fileService = $this->createMock(FileService::class);
        $fileService->expects(self::once())
            ->method('resolvePeopleReference')
            ->with('/people/18')
            ->willReturn($people);

        $hydrator = $this->createMock(HydratorService::class);
        $hydrator->expects(self::once())
            ->method('data')
            ->with(self::isInstanceOf(PeopleMedia::class), 'people_media:read')
            ->willReturn(['id' => 5, 'file' => ['id' => 77]]);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'people' => '/people/18',
                'mediaType' => '/media_types/12',
                'file' => '/files/77',
            ], JSON_THROW_ON_ERROR)
        );

        $response = (new PeopleMediaSaveController($manager, $fileService, $hydrator))($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['id' => 5, 'file' => ['id' => 77]], json_decode($response->getContent(), true));
    }

    public function testPostRejectsMediaTypeFromAnotherPeopleType(): void
    {
        $people = (new People())->setPeopleType('F');
        $mediaType = (new MediaType())->setType('stamp')->setPeopleType('J');
        $file = $this->createStub(File::class);

        $mediaTypeRepository = $this->createMock(EntityRepository::class);
        $mediaTypeRepository->method('find')->willReturn($mediaType);

        $fileRepository = $this->createMock(EntityRepository::class);
        $fileRepository->method('find')->willReturn($file);

        $manager = $this->createMock(EntityManagerInterface::class);
        $fallbackRepository = $this->createStub(EntityRepository::class);
        $manager->method('getRepository')->willReturnCallback(
            static fn(string $class) => match ($class) {
                MediaType::class => $mediaTypeRepository,
                File::class => $fileRepository,
                default => $fallbackRepository,
            }
        );
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $fileService = $this->createMock(FileService::class);
        $fileService->method('resolvePeopleReference')->willReturn($people);

        $hydrator = $this->createMock(HydratorService::class);
        $hydrator->expects(self::once())
            ->method('error')
            ->willReturn(['message' => 'media type does not match people type']);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'people' => '/people/18',
                'mediaType' => '/media_types/12',
                'file' => '/files/77',
            ], JSON_THROW_ON_ERROR)
        );

        $response = (new PeopleMediaSaveController($manager, $fileService, $hydrator))($request);

        self::assertSame(400, $response->getStatusCode());
    }
}
