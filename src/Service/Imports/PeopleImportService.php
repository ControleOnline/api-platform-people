<?php

namespace ControleOnline\Service\Imports;

use ControleOnline\Entity\Import;
use ControleOnline\Service\PeopleService;

class PeopleImportService extends ImportCommon
{

    private const CSV_HEADERS = [
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
    ];

    public function __construct(
        private PeopleService $peopleService
    ) {}

    public function getType(): string
    {
        return 'people';
    }

    public function process(Import $import): void
    {
        $this->import($import, self::CSV_HEADERS, $this->peopleService);
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
