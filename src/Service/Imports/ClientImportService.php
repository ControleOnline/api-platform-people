<?php

namespace ControleOnline\Service\Imports;

class ClientImportService extends PeopleImportService
{
    public function __construct() {}

    public function getType(): string
    {
        return 'client';
    }
}
