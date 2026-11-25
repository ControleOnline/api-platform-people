<?php

namespace ControleOnline\Service\Imports;

class ProspectImportService extends PeopleImportService
{

    public function getType(): string
    {
        return 'prospect';
    }

    protected function getLinkType(): string
    {
        return 'prospect';
    }
}
