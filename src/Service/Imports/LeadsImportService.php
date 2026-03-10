<?php

namespace ControleOnline\Service\Imports;

class LeadsImportService extends PeopleImportService
{
    public function __construct() {}

    public function getType(): string
    {
        return 'leads';
    }
}
