<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use ControleOnline\Entity\People;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccountRegistrationService
{
    public function __construct(
        private UserService $userService,
        private PeopleService $peopleService,
        private DomainService $domainService,
        private EntityManagerInterface $manager
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

        foreach (['name', 'email', 'phone'] as $field) {
            if (!isset($peopleData[$field])) {
                throw new BadRequestHttpException('people.name, people.email and people.phone are required');
            }
        }

        $phoneData = is_array($peopleData['phone'] ?? null) ? $peopleData['phone'] : [];
        foreach (['ddi', 'ddd', 'phone'] as $field) {
            if (!isset($phoneData[$field])) {
                throw new BadRequestHttpException('people.phone.ddi, people.phone.ddd and people.phone.phone are required');
            }
        }

        $personName = trim(sprintf(
            '%s %s',
            (string) ($peopleData['name'] ?? ''),
            (string) ($peopleData['alias'] ?? '')
        ));
        $personEmail = trim((string) ($peopleData['email'] ?? ''));
        $personDocument = $this->normalizeDocument($peopleData['document'] ?? null);
        $personPhone = $this->normalizePhoneData($phoneData);

        if ($personName === '') {
            throw new BadRequestHttpException('people.name is required');
        }

        if ($personEmail === '') {
            throw new BadRequestHttpException('people.email is required');
        }

        if ($personPhone['ddd'] === '' || $personPhone['phone'] === '') {
            throw new BadRequestHttpException('people.phone.ddd and people.phone.phone are required');
        }

        $this->assertPersonIdentifiersAreAvailable(
            $personDocument,
            $personEmail,
            $personPhone
        );

        $companyData = is_array($payload['company'] ?? null) ? $payload['company'] : null;
        if ($companyData) {
            $companyDocument = $this->normalizeDocument($companyData['document'] ?? null);
            $companyEmail = trim((string) ($companyData['email'] ?? ''));
            $companyPhone = $this->normalizePhoneData(
                is_array($companyData['phone'] ?? null) ? $companyData['phone'] : null
            );

            $this->assertCompanyIdentifiersAreAvailable(
                $companyDocument,
                $companyEmail,
                $companyPhone
            );
        }

        if (is_array($peopleData['user'] ?? null)) {
            $username = trim((string) ($peopleData['user']['user'] ?? ''));
            $password = (string) ($peopleData['user']['password'] ?? '');

            if ($username === '') {
                throw new BadRequestHttpException('people.user.user is required');
            }

            if ($password === '') {
                throw new BadRequestHttpException('people.user.password is required');
            }

            $this->assertUsernameIsAvailable($username);
        }

        $connection = $this->manager->getConnection();
        $connection->beginTransaction();

        try {
            $people = $this->peopleService->discoveryPeople(
                $personDocument,
                $personEmail,
                $personPhone,
                $personName,
                'F'
            );

            $client = $people;

            if ($companyData) {
                $company = $this->peopleService->discoveryPeople(
                    $this->normalizeDocument($companyData['document'] ?? null),
                    trim((string) ($companyData['email'] ?? '')) ?: null,
                    is_array($companyData['phone'] ?? null)
                        ? $this->normalizePhoneData($companyData['phone'])
                        : null,
                    trim((string) ($companyData['name'] ?? '')),
                    'J'
                );

                $this->peopleService->discoveryLink($company, $people, 'employee');
                $client = $company;
            }

            $mainCompany = $this->domainService->getPeopleDomain()->getPeople();
            $this->peopleService->discoveryLink($mainCompany, $client, 'client');

            if (is_array($peopleData['user'] ?? null)) {
                $this->userService->createUser(
                    $people,
                    trim((string) $peopleData['user']['user']),
                    (string) $peopleData['user']['password']
                );
            }

            $connection->commit();

            return $client;
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeDocument(mixed $document): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) ($document ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizePhoneData(?array $phoneData): array
    {
        $rawPhoneData = is_array($phoneData) ? $phoneData : [];

        return [
            'ddi' => preg_replace('/\D/', '', (string) ($rawPhoneData['ddi'] ?? '55')) ?: '55',
            'ddd' => preg_replace('/\D/', '', (string) ($rawPhoneData['ddd'] ?? '')),
            'phone' => preg_replace('/\D/', '', (string) ($rawPhoneData['phone'] ?? '')),
        ];
    }

    private function assertPersonIdentifiersAreAvailable(
        ?string $document,
        string $email,
        array $phoneData
    ): void {
        if ($document && $this->peopleService->getDocument($document)) {
            throw new ConflictHttpException('Este CPF já está cadastrado.');
        }

        if ($this->peopleService->getEmail($email)) {
            throw new ConflictHttpException('Este e-mail já está cadastrado.');
        }

        if (
            $phoneData['ddd'] !== '' &&
            $phoneData['phone'] !== '' &&
            $this->peopleService->getPhone(
                (int) $phoneData['ddi'],
                (int) $phoneData['ddd'],
                $phoneData['phone']
            )
        ) {
            throw new ConflictHttpException('Este telefone já está cadastrado.');
        }
    }

    private function assertCompanyIdentifiersAreAvailable(
        ?string $document,
        string $email,
        array $phoneData
    ): void {
        if ($document && $this->peopleService->getDocument($document, 'CNPJ')) {
            throw new ConflictHttpException('Este CNPJ já está cadastrado.');
        }

        if ($email !== '' && $this->peopleService->getEmail($email)) {
            throw new ConflictHttpException('Este e-mail já está cadastrado.');
        }

        if (
            $phoneData['ddd'] !== '' &&
            $phoneData['phone'] !== '' &&
            $this->peopleService->getPhone(
                (int) $phoneData['ddi'],
                (int) $phoneData['ddd'],
                $phoneData['phone']
            )
        ) {
            throw new ConflictHttpException('Este telefone já está cadastrado.');
        }
    }

    private function assertUsernameIsAvailable(string $username): void
    {
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'username' => $username,
        ]);

        if ($user instanceof User) {
            throw new ConflictHttpException('Este usuário já está cadastrado.');
        }
    }
}
