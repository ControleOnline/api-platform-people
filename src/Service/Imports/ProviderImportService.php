<?php

namespace ControleOnline\Service\Imports;

class ProviderImportService extends PeopleImportService
{

    public function getType(): string
    {
        return 'provider';
    }
}
