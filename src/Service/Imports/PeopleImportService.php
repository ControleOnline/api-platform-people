<?php

namespace ControleOnline\Service\Imports;

use ControleOnline\Entity\Import;
use ControleOnline\Service\PeopleService;

class PeopleImportService extends AbstractCsvImportProcessor
{
    public function __construct(
        private PeopleService $peopleService
    ) {}

    public function getType(): string
    {
        return 'people';
    }



    public function process(Import $import): void
    {
        $file = $import->getFile();

        $rows = explode("\n", $file->getContent());

        foreach ($rows as $index => $row) {

            if ($index === 0 || trim($row) === '') {
                continue;
            }

            $data = str_getcsv($row);

            [
                $company,
                $document,
                $segment,
                $contact,
                $phones,
                $emails,
                $address,
                $bairro,
                $city,
                $uf,
                $cep,
                $number,
                $complement

            ] = $data;

            $phones = array_filter(array_map('trim', explode(',', $phones)));
            $emails = array_filter(array_map('trim', explode(',', $emails)));

            $this->peopleService->importPeopleFromCSV(
                $company,
                $document,
                $segment,
                $contact,
                $phones,
                $emails,
                $address,
                $city,
                $uf,
                $cep,
                $number,
                $complement,
                $import->getImportType(),
                $import->getPeople()
            );
        }
    }


    public function getExampleCsv(): string
    {
        $rows = [
            [
                'Empresa',
                'CNPJ',
                'Segmento',
                'Responsavel',
                'Telefones',
                'Emails',
                'Endereco',
                'Bairro',
                'Cidade',
                'Estado',
                'CEP',
                'Numero',
                'Complemento'
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

        return $this->generateUtf8Csv($rows);
    }
}
