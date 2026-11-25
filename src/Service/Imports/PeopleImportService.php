<?php

namespace ControleOnline\Service\Imports;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\People;
use ControleOnline\Service\AddressService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\ProspectAssignmentService;

class PeopleImportService extends ImportCommon
{

    private const CSV_HEADERS = PeopleService::IMPORT_CSV_HEADERS;

    private ?Import $currentImport = null;

    public function __construct(
        private PeopleService $peopleService,
        private ProspectAssignmentService $prospectAssignmentService,
        private AddressService $addressService
    ) {}

    public function getType(): string
    {
        return 'people';
    }

    /** PeopleLink type given to imported rows (company -> imported people). */
    protected function getLinkType(): string
    {
        return 'client';
    }

    public function process(Import $import): void
    {
        $this->currentImport = $import;

        try {
            // ImportCommon calls importFromCSV() on the given service for every row.
            $this->import($import, self::CSV_HEADERS, $this);
        } finally {
            $this->currentImport = null;
        }
    }

    public function importFromCSV(array $rowData, People $provider): People
    {
        $people = $this->peopleService->importFromCSV($rowData, $provider, $this->getLinkType());
        $row = $this->peopleService->normalizeImportRow($rowData);

        $this->importAddress($people, $row);

        $uploader = $this->currentImport && method_exists($this->currentImport, 'getCreatedBy')
            ? $this->currentImport->getCreatedBy()
            : null;

        $this->prospectAssignmentService->assign($provider, $people, $this->getLinkType(), $uploader);

        return $people;
    }

    private function importAddress(People $people, array $row): void
    {
        $cep = preg_replace('/\D/', '', $row['CEP']);
        if ($cep === '' || $row['Endereco'] === '' || $row['Cidade'] === '' || $row['Estado'] === '') {
            return;
        }

        try {
            $this->addressService->discoveryAddress(
                $cep,
                $row['Numero'] !== '' ? $row['Numero'] : null,
                $row['Endereco'],
                $row['Bairro'],
                $row['Cidade'],
                strtoupper($row['Estado']),
                'BR',
                $people,
                $row['Complemento'] !== '' ? $row['Complemento'] : null
            );
        } catch (\Throwable) {
            // An unknown CEP/city must not reject the people already imported.
        }
    }

    public function getExampleCsv(): array
    {
        return [
            [
                ...self::CSV_HEADERS,
            ],
            [
                'Empresa Exemplo LTDA',
                '12345678000199',
                'Tecnologia',
                'João Silva',
                '11999999999,11888888888',
                'contato@empresa.com,financeiro@empresa.com',
                'Rua das Flores',
                'Jd Logo Ali',
                'São Paulo',
                'SP',
                '01001000',
                '100',
                'Sala 10'
            ]
        ];
    }
}
