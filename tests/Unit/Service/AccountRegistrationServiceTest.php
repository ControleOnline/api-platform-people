<?php

namespace ControleOnline\Tests\Unit\Service;

use ControleOnline\Entity\Email;
use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AccountRegistrationServiceTest extends TestCase
{
    public function testItRejectsAlreadyRegisteredEmailBeforePersisting(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $userService = $this->createMock(UserService::class);
        $domainService = $this->createMock(DomainService::class);
        $connection = $this->createMock(Connection::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getConnection')
            ->willReturn($connection);

        $peopleService
            ->method('getEmail')
            ->with('maria@teste.com')
            ->willReturn($this->createMock(Email::class));

        $service = new AccountRegistrationService(
            $userService,
            $peopleService,
            $domainService,
            $entityManager
        );

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Este e-mail já está cadastrado.');

        $service->registerFromPayload([
            'people' => [
                'name' => 'Maria',
                'alias' => 'Maria',
                'email' => 'maria@teste.com',
                'document' => '12345678909',
                'phone' => [
                    'ddi' => '55',
                    'ddd' => '11',
                    'phone' => '999999999',
                ],
                'user' => [
                    'user' => 'maria',
                    'password' => '123456',
                ],
            ],
        ]);
    }
}
