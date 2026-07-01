<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\GetPublicShopFranchisesAction;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\District;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Street;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\PeopleRoleService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GetPublicShopFranchisesActionTest extends TestCase
{
    public function testInvokeReturnsOnlyConfiguredPublicFranchises(): void
    {
        $mainCompany = new People();
        $this->setEntityId($mainCompany, 10);

        $franchise = new People();
        $this->setEntityId($franchise, 21);
        $franchise->setName('Franquia Centro');
        $franchise->setAlias('Centro');

        $phone = new Phone();
        $this->setEntityId($phone, 301);
        $phone->setDdi(55);
        $phone->setDdd(11);
        $phone->setPhone(999999999);
        $franchise->getPhone()->add($phone);

        $visibleAddress = $this->createAddress(
            id: 501,
            nickname: 'Loja Centro',
            streetName: 'Rua A',
            streetNumber: 123,
            districtName: 'Centro',
            cityName: 'Sao Paulo',
            stateUf: 'SP',
            postalCode: 12345678,
            searchFor: 'Rua A, 123 - Sao Paulo',
            latitude: -23.55,
            longitude: -46.63,
        );
        $hiddenAddress = $this->createAddress(
            id: 502,
            nickname: 'Loja Oculta',
            streetName: 'Rua B',
            streetNumber: 456,
            districtName: 'Bela Vista',
            cityName: 'Sao Paulo',
            stateUf: 'SP',
            postalCode: 87654321,
            searchFor: 'Rua B, 456 - Sao Paulo',
            latitude: -23.56,
            longitude: -46.64,
        );
        $franchise->getAddress()->add($visibleAddress);
        $franchise->getAddress()->add($hiddenAddress);

        $visibleCompanyConfig = new Config();
        $visibleCompanyConfig->setConfigKey('shop-franchise-visible-company-ids');
        $visibleCompanyConfig->setConfigValue('[21]');

        $visibleAddressConfig = new Config();
        $visibleAddressConfig->setConfigKey('shop-franchise-visible-address-ids');
        $visibleAddressConfig->setConfigValue('[501]');

        $roles = $this->createMock(PeopleRoleService::class);
        $roles
            ->expects(self::once())
            ->method('getMainCompany')
            ->willReturn($mainCompany);

        $configService = $this->createMock(ConfigService::class);
        $configService
            ->expects(self::once())
            ->method('getCompanyConfigs')
            ->with($mainCompany, 'public')
            ->willReturn([$visibleCompanyConfig, $visibleAddressConfig]);

        $peopleRepository = $this->createMock(PeopleRepository::class);
        $peopleRepository
            ->expects(self::once())
            ->method('countPublicShopFranchises')
            ->with($mainCompany, [21], '')
            ->willReturn(1);
        $peopleRepository
            ->expects(self::once())
            ->method('findPublicShopFranchises')
            ->with($mainCompany, [21], '', 1, 30)
            ->willReturn([$franchise]);

        $controller = new GetPublicShopFranchisesAction(
            $roles,
            $configService,
            $peopleRepository
        );

        $response = $controller->__invoke(new Request());
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $payload['totalItems']);
        self::assertSame(1, $payload['page']);
        self::assertSame(30, $payload['itemsPerPage']);
        self::assertCount(1, $payload['member']);
        self::assertSame('CENTRO', $payload['member'][0]['alias']);
        self::assertCount(1, $payload['member'][0]['shopAddresses']);
        self::assertSame(501, $payload['member'][0]['shopAddresses'][0]['id']);
        self::assertSame(
            'Rua A, 123 - Sao Paulo',
            $payload['member'][0]['shopAddresses'][0]['searchFor']
        );
        self::assertSame('08:00 - 18:00', $payload['member'][0]['shopAddresses'][0]['openingHours']);
    }

    private function createAddress(
        int $id,
        string $nickname,
        string $streetName,
        int $streetNumber,
        string $districtName,
        string $cityName,
        string $stateUf,
        int $postalCode,
        string $searchFor,
        float $latitude,
        float $longitude,
    ): Address {
        $state = new State();
        $state->setState($stateUf);
        $state->setUf($stateUf);

        $city = new City();
        $city->setCity($cityName);
        $city->setState($state);

        $district = new District();
        $district->setDistrict($districtName);
        $district->setCity($city);

        $cep = new Cep();
        $cep->setCep($postalCode);

        $street = new Street();
        $street->setStreet($streetName);
        $street->setDistrict($district);
        $street->setCep($cep);

        $address = new Address();
        $this->setEntityId($address, $id);
        $address->setNickname($nickname);
        $address->setNumber($streetNumber);
        $address->setComplement('');
        $address->setStreet($street);
        $address->setLatitude($latitude);
        $address->setLongitude($longitude);
        $address->setSearchFor($searchFor);
        $address->setOpeningTime(new \DateTimeImmutable('08:00'));
        $address->setClosingTime(new \DateTimeImmutable('18:00'));

        return $address;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
