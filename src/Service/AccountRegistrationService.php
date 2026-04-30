<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccountRegistrationService
{
    public function __construct(
        private UserService $userService,
        private PeopleService $peopleService,
        private DomainService $domainService
    ) {}

    public function registerFromContent(?string $content): People
    {
        return $this->registerFromPayload($this->decodePayload($content));
    }

    public function registerFromPayload(array $payload): People
    {
        $peopleData = $payload['people'] ?? null;
        if (!is_array($peopleData)) {
            throw new BadRequestHttpException('people is required');
        }

        foreach (['name', 'alias', 'email', 'phone'] as $field) {
            if (!isset($peopleData[$field])) {
                throw new BadRequestHttpException('name, alias, email and phone are required');
            }
        }

        $phoneData = is_array($peopleData['phone'] ?? null) ? $peopleData['phone'] : [];
        foreach (['ddi', 'ddd', 'phone'] as $field) {
            if (!isset($phoneData[$field])) {
                throw new BadRequestHttpException('phone.ddi, phone.ddd and phone.number are required');
            }
        }

        $people = $this->peopleService->discoveryPeople(
            $peopleData['document'] ?? null,
            $peopleData['email'],
            $phoneData,
            trim($peopleData['name'] . ' ' . $peopleData['alias']),
            'F'
        );

        $client = $people;

        if (is_array($payload['company'] ?? null)) {
            $companyData = $payload['company'];
            $company = $this->peopleService->discoveryPeople(
                $companyData['document'] ?? null,
                $companyData['email'] ?? null,
                is_array($companyData['phone'] ?? null) ? $companyData['phone'] : null,
                $companyData['name'] ?? null,
                'J'
            );

            $this->peopleService->discoveryLink($company, $people, 'employee');
            $client = $company;
        }

        $mainCompany = $this->domainService->getPeopleDomain()->getPeople();
        $this->peopleService->discoveryLink($mainCompany, $client, 'client');

        if (is_array($peopleData['user'] ?? null)) {
            if (
                !isset($peopleData['user']['user']) ||
                !isset($peopleData['user']['password'])
            ) {
                throw new BadRequestHttpException('user.user and user.password are required');
            }

            $this->userService->createUser(
                $people,
                $peopleData['user']['user'],
                $peopleData['user']['password']
            );
        }

        return $client;
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
