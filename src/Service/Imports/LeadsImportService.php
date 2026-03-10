<?php

namespace ControleOnline\Service\Imports;

class LeadsImportService extends PeopleImportService
{

    public function getType(): string
    {
        return 'leads';
    }
}
