<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\PeopleRoleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetPublicShopFranchisesAction
{
    private const VISIBLE_COMPANY_IDS_CONFIG_KEY =
        'shop-franchise-visible-company-ids';
    private const VISIBLE_ADDRESS_IDS_CONFIG_KEY =
        'shop-franchise-visible-address-ids';

    public function __construct(
        private PeopleRoleService $roles,
        private ConfigService $configService,
        private PeopleRepository $peopleRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $mainCompany = $this->roles->getMainCompany();
            if (!$mainCompany instanceof People) {
                return new JsonResponse($this->buildCollection([]));
            }

            $configMap = $this->buildConfigMap(
                $this->configService->getCompanyConfigs($mainCompany, 'public')
            );
            $visibleCompanyIds = $this->normalizeEntityIds(
                $configMap[self::VISIBLE_COMPANY_IDS_CONFIG_KEY] ?? null
            );

            if ($visibleCompanyIds === []) {
                return new JsonResponse($this->buildCollection([]));
            }

            $visibleAddressIds = $this->normalizeEntityIds(
                $configMap[self::VISIBLE_ADDRESS_IDS_CONFIG_KEY] ?? null
            );
            $search = (string) $request->query->get('search', '');

            $companies = $this->peopleRepository->findPublicShopFranchises(
                $mainCompany,
                $visibleCompanyIds,
                $search
            );
            $items = array_map(
                fn(People $company): array => $this->serializeCompany(
                    $company,
                    $visibleAddressIds
                ),
                $companies
            );

            return new JsonResponse($this->buildCollection($items));
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'member' => [],
                'hydra:member' => [],
                'totalItems' => 0,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * @param Config[] $configs
     * @return array<string, mixed>
     */
    private function buildConfigMap(array $configs): array
    {
        $configMap = [];

        foreach ($configs as $config) {
            if (!$config instanceof Config) {
                continue;
            }

            $configMap[$config->getConfigKey()] = $config->getConfigValue();
        }

        return $configMap;
    }

    /**
     * @return int[]
     */
    private function normalizeEntityIds(mixed $value): array
    {
        if (is_string($value)) {
            $trimmedValue = trim($value);
            if ($trimmedValue === '') {
                return [];
            }

            try {
                $decoded = json_decode($trimmedValue, true, 512, JSON_THROW_ON_ERROR);
                $value = $decoded;
            } catch (\JsonException) {
                $value = preg_split('/[\r\n,]+/', $trimmedValue) ?: [];
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $normalizedIds = array_map(
            static function (mixed $item): int {
                if (is_array($item)) {
                    $item = $item['id'] ?? $item['@id'] ?? null;
                }

                return (int) preg_replace('/\D+/', '', (string) ($item ?? ''));
            },
            $value
        );

        return array_values(array_unique(array_filter(
            $normalizedIds,
            static fn(int $item): bool => $item > 0
        )));
    }

    /**
     * @param int[] $visibleAddressIds
     * @return array<string, mixed>
     */
    private function serializeCompany(People $company, array $visibleAddressIds): array
    {
        $addresses = [];
        foreach ($company->getAddress() as $address) {
            if (!$address instanceof Address) {
                continue;
            }

            $addressId = (int) $address->getId();
            if (
                $visibleAddressIds !== []
                && !in_array($addressId, $visibleAddressIds, true)
            ) {
                continue;
            }

            $addresses[] = $this->serializeAddress($address);
        }

        return [
            '@id' => '/people/' . $company->getId(),
            'id' => (int) $company->getId(),
            'name' => $company->getName(),
            'alias' => $company->getAlias(),
            'phone' => $this->serializePhones($company->getPhone()),
            'mobile' => [],
            'whatsapp' => [],
            'shopAddresses' => $addresses,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializePhones(iterable $phones): array
    {
        $items = [];

        foreach ($phones as $phone) {
            if (!$phone instanceof Phone) {
                continue;
            }

            $items[] = [
                'id' => $phone->getId(),
                'ddi' => $phone->getDdi(),
                'ddd' => $phone->getDdd(),
                'phone' => $phone->getPhone(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAddress(Address $address): array
    {
        $street = $address->getStreet();
        $district = $street?->getDistrict();
        $city = $district?->getCity();
        $state = $city?->getState();
        $cep = $street?->getCep();
        $openingTime = $this->formatTime($address->getOpeningTime());
        $closingTime = $this->formatTime($address->getClosingTime());

        return [
            '@id' => '/addresses/' . $address->getId(),
            'id' => (int) $address->getId(),
            'nickname' => $address->getNickname(),
            'number' => $address->getNumber(),
            'complement' => $address->getComplement(),
            'latitude' => (float) $address->getLatitude(),
            'longitude' => (float) $address->getLongitude(),
            'searchFor' => $address->getSearchFor(),
            'search_for' => $address->getSearchFor(),
            'openingTime' => $openingTime,
            'closingTime' => $closingTime,
            'openingHours' => $this->formatOpeningHours($openingTime, $closingTime),
            'street' => [
                'street' => $street?->getStreet() ?? '',
                'district' => [
                    'district' => $district?->getDistrict() ?? '',
                    'city' => [
                        'city' => $city?->getCity() ?? '',
                        'state' => [
                            'uf' => $state?->getUf() ?? '',
                            'state' => $state?->getState() ?? '',
                        ],
                    ],
                ],
                'cep' => [
                    'cep' => $cep?->getCep() ?? '',
                ],
            ],
        ];
    }

    private function formatTime(mixed $time): string
    {
        if (!$time instanceof \DateTimeInterface) {
            return '';
        }

        return $time->format('H:i');
    }

    private function formatOpeningHours(string $openingTime, string $closingTime): string
    {
        if ($openingTime !== '' && $closingTime !== '') {
            return sprintf('%s - %s', $openingTime, $closingTime);
        }

        return $openingTime ?: $closingTime;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function buildCollection(array $items): array
    {
        return [
            'member' => array_values($items),
            'hydra:member' => array_values($items),
            'totalItems' => count($items),
        ];
    }
}
