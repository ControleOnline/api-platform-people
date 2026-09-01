<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\User;
use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\AccountVerificationService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $manager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->createUserRepository(0, null));

        $peopleService = $this->createAvailablePeopleService();
        $peopleService
            ->expects(self::once())
            ->method('discoveryPeople')
            ->with(
                '52998224725',
                'alemac@mac.com',
                ['ddi' => '55', 'ddd' => '11', 'phone' => '999999999'],
                'ALEMAC',
                'F'
            )
            ->willReturn($person);
        $peopleService
            ->expects(self::once())
            ->method('discoveryLink')
            ->willReturnCallback(static function (People $company, People $linkedPeople, string $linkType) use ($mainCompany, $person): PeopleLink {
                self::assertSame($mainCompany, $company);
                self::assertSame($person, $linkedPeople);
                self::assertSame('owner', $linkType);

                return new PeopleLink();
            });

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
        $connection->expects(self::once())->method('rollBack');
        $connection->method('isTransactionActive')->willReturn(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $manager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->createUserRepository(1, null));

        $peopleService = $this->createAvailablePeopleService();
        $peopleService->method('discoveryPeople')->willReturn($person);
        $peopleService
            ->expects(self::once())
            ->method('discoveryLink')
            ->willReturn(new PeopleLink());

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

    public function testRegisterFromPayloadRejectsDuplicateEmailWithoutPersisting(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('beginTransaction');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getDocument')->willReturn(null);
        $peopleService
            ->expects(self::once())
            ->method('getEmail')
            ->with('alemac@mac.com')
            ->willReturn($this->createMock(\ControleOnline\Entity\Email::class));
        $peopleService->expects(self::never())->method('discoveryPeople');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::never())->method('createUser');

        $domainService = $this->createMock(DomainService::class);
        $verificationService = $this->createMock(AccountVerificationService::class);

        $service = new AccountRegistrationService(
            $manager,
            $userService,
            $peopleService,
            $domainService,
            $verificationService,
        );

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Este e-mail já está cadastrado.');

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

    private function createAvailablePeopleService(): PeopleService&\PHPUnit\Framework\MockObject\MockObject
    {
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getDocument')->willReturn(null);
        $peopleService->method('getEmail')->willReturn(null);
        $peopleService->method('getPhone')->willReturn(null);

        return $peopleService;
    }

    private function createUserRepository(int $count, ?User $existingUser): EntityRepository
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count', 'findOneBy'])
            ->getMock();
        $repository
            ->method('count')
            ->with([])
            ->willReturn($count);
        $repository
            ->method('findOneBy')
            ->willReturn($existingUser);

        return $repository;
    }
}
