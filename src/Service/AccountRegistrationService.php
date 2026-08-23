<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AccountRegistrationService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserService $userService,
        private PeopleService $peopleService,
        private DomainService $domainService,
        private AccountVerificationService $accountVerificationService,
        private ?MarketingEventService $marketingEventService = null,
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

        $phoneData = $this->normalizePhoneData(
            is_array($peopleData['phone'] ?? null) ? $peopleData['phone'] : []
        );
        foreach (['ddi', 'ddd', 'phone'] as $field) {
            if (!isset($phoneData[$field])) {
                throw new BadRequestHttpException('phone.ddi, phone.ddd and phone.number are required');
            }
        }

        $personName = trim((string) $peopleData['name']);
        $personEmail = trim((string) $peopleData['email']);
        $personDocument = $this->normalizeDocument($peopleData['document'] ?? null);

        if ($personName === '') {
            throw new BadRequestHttpException('name is required');
        }
        if ($personEmail === '') {
            throw new BadRequestHttpException('email is required');
        }

        $this->assertPersonIdentifiersAreAvailable($personDocument, $personEmail, $phoneData);

        $companyData = is_array($payload['company'] ?? null) ? $payload['company'] : null;
        if ($companyData !== null) {
            $companyDocument = $this->normalizeDocument($companyData['document'] ?? null);
            $companyEmail = trim((string) ($companyData['email'] ?? ''));
            $companyPhone = $this->normalizePhoneData(
                is_array($companyData['phone'] ?? null) ? $companyData['phone'] : []
            );
            $this->assertCompanyIdentifiersAreAvailable($companyDocument, $companyEmail, $companyPhone);
        }

        $registersUser = is_array($peopleData['user'] ?? null);
        if ($registersUser) {
            if (
                !isset($peopleData['user']['user']) ||
                !isset($peopleData['user']['password'])
            ) {
                throw new BadRequestHttpException('user.user and user.password are required');
            }
            $username = trim((string) $peopleData['user']['user']);
            if ($username === '') {
                throw new BadRequestHttpException('user.user is required');
            }
            $this->assertUsernameIsAvailable($username);
        }

        $connection = $this->manager->getConnection();
        $connection->beginTransaction();

        try {
            $isFirstTenantUser = $this->isFirstTenantUser();

            $people = $this->peopleService->discoveryPeople(
                $personDocument,
                $personEmail,
                $phoneData,
                $personName,
                'F'
            );
            $this->applyPeopleName($people, $peopleData);

            $client = $people;

            if ($companyData !== null) {
                $company = $this->peopleService->discoveryPeople(
                    $this->normalizeDocument($companyData['document'] ?? null),
                    $companyData['email'] ?? null,
                    is_array($companyData['phone'] ?? null) ? $this->normalizePhoneData($companyData['phone']) : null,
                    $companyData['name'] ?? null,
                    'J'
                );
                $this->applyPeopleName($company, $companyData);

                $this->peopleService->discoveryLink($company, $people, 'employee');
                $client = $company;
            }

            $mainCompany = $this->domainService->getPeopleDomain()->getPeople();

            if (!$isFirstTenantUser || !$registersUser || $client !== $people) {
                $this->peopleService->discoveryLink($mainCompany, $client, 'client');
            }

            if ($registersUser) {
                $user = $this->userService->createUser(
                    $people,
                    $peopleData['user']['user'],
                    $peopleData['user']['password']
                );

                if ($isFirstTenantUser) {
                    $this->peopleService->discoveryLink($mainCompany, $people, 'owner');
                }

                $this->accountVerificationService->sendVerification(
                    $user,
                    $peopleData['email'] ?? null
                );
            }

            $this->associateMarketingVisitor($payload, $client);

            $connection->commit();
            return $client;
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function isFirstTenantUser(): bool
    {
        return $this->manager->getRepository(User::class)->count([]) === 0;
    }

    private function normalizePhoneData(array $phoneData): array
    {
        foreach (['ddi', 'ddd', 'phone'] as $field) {
            if (isset($phoneData[$field])) {
                $phoneData[$field] = preg_replace('/\D+/', '', (string) $phoneData[$field]);
            }
        }

        $ddd = (string) ($phoneData['ddd'] ?? '');
        $phone = (string) ($phoneData['phone'] ?? '');

        if ($ddd !== '' && strlen($phone) > 9 && str_starts_with($phone, $ddd)) {
            $phoneData['phone'] = substr($phone, strlen($ddd));
        }

        return $phoneData;
    }

    private function normalizeDocument(mixed $document): ?string
    {
        if ($document === null || $document === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $document);

        return $digits !== '' ? $digits : null;
    }

    private function applyPeopleName(People $people, array $peopleData): void
    {
        $name = trim((string) ($peopleData['name'] ?? ''));
        $alias = trim((string) ($peopleData['alias'] ?? ''));

        if ($name !== '') {
            $people->setName($name);
        }

        if ($alias !== '') {
            $people->setAlias($alias);
        }

        $this->manager->persist($people);
        $this->manager->flush();
    }

    /**
     * Link prior anonymous marketing events (visitor_id) to the newly created People.
     *
     * @param array<string, mixed> $payload
     */
    private function associateMarketingVisitor(array $payload, People $people): void
    {
        if ($this->marketingEventService === null) {
            return;
        }

        $marketing = $payload['marketing'] ?? null;
        if (!is_array($marketing)) {
            return;
        }

        $visitorId = isset($marketing['visitor_id'])
            ? trim((string) $marketing['visitor_id'])
            : '';
        if ($visitorId === '') {
            return;
        }

        $this->marketingEventService->associateVisitorToPeople($visitorId, $people);
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
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

        $ddd = (string) ($phoneData['ddd'] ?? '');
        $phone = (string) ($phoneData['phone'] ?? '');
        $ddi = (string) ($phoneData['ddi'] ?? '55');

        if (
            $ddd !== '' &&
            $phone !== '' &&
            $this->peopleService->getPhone((int) $ddi, (int) $ddd, $phone)
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

        $ddd = (string) ($phoneData['ddd'] ?? '');
        $phone = (string) ($phoneData['phone'] ?? '');
        $ddi = (string) ($phoneData['ddi'] ?? '55');

        if (
            $ddd !== '' &&
            $phone !== '' &&
            $this->peopleService->getPhone((int) $ddi, (int) $ddd, $phone)
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
