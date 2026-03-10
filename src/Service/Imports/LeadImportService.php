<?php

namespace ControleOnline\Service\Imports;

class LeadImportService extends PeopleImportService
{

    public function getType(): string
    {
        return 'lead';
    }
}
