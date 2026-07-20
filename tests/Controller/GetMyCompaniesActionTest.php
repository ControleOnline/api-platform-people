<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\GetMyCompaniesAction;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GetMyCompaniesActionTest extends TestCase
{
    public function testInvokeFiltersCourierCompaniesWhenRequested(): void
    {
        $userPeople = new People();
        $this->setEntityId($userPeople, 7);
        $userPeople->setName('Motoboy Teste');
        $userPeople->setAlias('Motoboy Teste');
        $userPeople->setEnabled(true);

        $company = new People();
        $this->setEntityId($company, 21);
        $company->setName('Restaurante Centro');
        $company->setAlias('Centro');
        $company->setEnabled(true);

        $link = new PeopleLink();
        $link->setPeople($userPeople);
        $link->setCompany($company);
        $link->setLinkType('courier');
        $link->setEnabled(true);
        $link->setComission(0);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(new class($userPeople) implements UserInterface {
                public function __construct(private People $people)
                {
                }

                public function getPeople(): People
                {
                    return $this->people;
                }

                public function getUserIdentifier(): string
                {
                    return 'test-user';
                }

                public function getRoles(): array
                {
                    return [];
                }

                public function eraseCredentials(): void
                {
                }
            });

        $security = $this->createMock(TokenStorageInterface::class);
        $security
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService
            ->expects(self::once())
            ->method('getDirectLinksForPeople')
            ->with($userPeople, ['courier'])
            ->willReturn([$link]);
        $peopleRoleService
            ->expects(self::once())
            ->method('companyHasPanelAccess')
            ->with($company)
            ->willReturn(true);
        $peopleRoleService
            ->expects(self::once())
            ->method('getCompanyPermissions')
            ->with($company, $userPeople)
            ->willReturn(['courier']);

        $repository = $this->createStub(EntityRepository::class);
        $repository
            ->method('findBy')
            ->willReturn([]);

        $entityManager = $this->createStub(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturn($repository);

        $logoUrl = [
            'id' => 91,
            'domain' => 'https://cdn.example.test',
            'url' => '/files/91/download',
        ];
        $iconUrl = [
            'id' => 92,
            'domain' => 'https://cdn.example.test',
            'url' => '/files/92/download',
        ];
        $stampUrl = [
            'id' => 93,
            'domain' => 'https://cdn.example.test',
            'url' => '/files/93/download',
        ];

        $fileService = $this->createMock(FileService::class);
        $fileService
            ->expects(self::exactly(3))
            ->method('getPeopleMediaFileUrl')
            ->willReturnCallback(static function (People $people, string $mediaType) use ($company, $logoUrl, $iconUrl, $stampUrl) {
                self::assertSame($company->getId(), $people->getId());

                return match ($mediaType) {
                    'icon' => $iconUrl,
                    'stamp' => $stampUrl,
                    default => $logoUrl,
                };
            });
        $fileService
            ->expects(self::never())
            ->method('getFileUrl');

        $controller = new GetMyCompaniesAction(
            $security,
            $entityManager,
            $this->createStub(DomainService::class),
            $peopleRoleService,
            $fileService
        );

        $response = $controller->__invoke(new Request([
            'linkType' => 'courier',
        ]));
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $payload['response']['count']);
        self::assertCount(1, $payload['response']['data']);
        self::assertSame(21, $payload['response']['data'][0]['id']);
        self::assertSame('CENTRO', $payload['response']['data'][0]['alias']);
        self::assertSame($logoUrl, $payload['response']['data'][0]['logo']);
        self::assertSame($iconUrl, $payload['response']['data'][0]['icon']);
        self::assertSame($stampUrl, $payload['response']['data'][0]['stamp']);
        self::assertTrue($payload['response']['data'][0]['user']['courier_enabled']);
        self::assertSame(['courier'], $payload['response']['data'][0]['permission']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
