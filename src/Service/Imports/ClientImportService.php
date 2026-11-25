<?php

namespace ControleOnline\Service\Imports;

class ClientImportService extends PeopleImportService
{
    public function getType(): string
    {
        return 'client';
    }

    protected function getLinkType(): string
    {
        return 'client';
    }
}
