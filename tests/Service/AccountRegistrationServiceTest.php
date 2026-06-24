<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\User;
use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\AccountVerificationService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AccountRegistrationServiceTest extends TestCase
{
    public function testRegisterFromPayloadCommitsAndSendsVerification(): void
    {
        $person = new People();
        $mainCompany = new People();
        $peopleDomain = new PeopleDomain();
        $peopleDomain->setPeople($mainCompany);

        $user = new User();
        $user->setUsername('alemac@mac.com');
        $user->setPeople($person);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService
            ->expects(self::once())
            ->method('discoveryPeople')
            ->with(
                '52998224725',
                'alemac@mac.com',
                ['ddi' => '55', 'ddd' => '11', 'phone' => '999999999'],
                'ALEMAC TESTE',
                'F'
            )
            ->willReturn($person);
        $peopleService
            ->expects(self::once())
            ->method('discoveryLink')
            ->with($mainCompany, $person, 'client');

        $userService = $this->createMock(UserService::class);
        $userService
            ->expects(self::once())
            ->method('createUser')
            ->with($person, 'alemac@mac.com', '123456')
            ->willReturn($user);

        $domainService = $this->createMock(DomainService::class);
        $domainService
            ->expects(self::once())
            ->method('getPeopleDomain')
            ->willReturn($peopleDomain);

        $verificationService = $this->createMock(AccountVerificationService::class);
        $verificationService
            ->expects(self::once())
            ->method('sendVerification')
            ->with($user, 'alemac@mac.com');

        $service = new AccountRegistrationService(
            $manager,
            $userService,
            $peopleService,
            $domainService,
            $verificationService,
        );

        $registeredPeople = $service->registerFromPayload([
            'people' => [
                'document' => '52998224725',
                'name' => 'ALEMAC',
                'alias' => 'TESTE',
                'email' => 'alemac@mac.com',
                'phone' => [
                    'ddi' => '55',
                    'ddd' => '11',
                    'phone' => '999999999',
                ],
                'user' => [
                    'user' => 'alemac@mac.com',
                    'password' => '123456',
                ],
            ],
        ]);

        self::assertSame($person, $registeredPeople);
    }

    public function testRegisterFromPayloadRollsBackWhenVerificationFails(): void
    {
        $person = new People();
        $mainCompany = new People();
        $peopleDomain = new PeopleDomain();
        $peopleDomain->setPeople($mainCompany);

        $user = new User();
        $user->setUsername('alemac@mac.com');
        $user->setPeople($person);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::never())->method('commit');
        $connection->expects(self::once())->method('isTransactionActive')->willReturn(true);
        $connection->expects(self::once())->method('rollBack');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('discoveryPeople')->willReturn($person);
        $peopleService->expects(self::once())->method('discoveryLink');

        $userService = $this->createMock(UserService::class);
        $userService->method('createUser')->willReturn($user);

        $domainService = $this->createMock(DomainService::class);
        $domainService->method('getPeopleDomain')->willReturn($peopleDomain);

        $verificationService = $this->createMock(AccountVerificationService::class);
        $verificationService
            ->expects(self::once())
            ->method('sendVerification')
            ->willThrowException(new \Exception('Mail transport failed'));

        $service = new AccountRegistrationService(
            $manager,
            $userService,
            $peopleService,
            $domainService,
            $verificationService,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mail transport failed');

        $service->registerFromPayload([
            'people' => [
                'document' => '52998224725',
                'name' => 'ALEMAC',
                'alias' => 'TESTE',
                'email' => 'alemac@mac.com',
                'phone' => [
                    'ddi' => '55',
                    'ddd' => '11',
                    'phone' => '999999999',
                ],
                'user' => [
                    'user' => 'alemac@mac.com',
                    'password' => '123456',
                ],
            ],
        ]);
    }
}
