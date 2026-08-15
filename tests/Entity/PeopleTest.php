<?php

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\People;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class PeopleTest extends TestCase
{
    public function testCompanyDocumentCollectionStartsInitialized(): void
    {
        $people = new People();

        $companyDocuments = $people->getCompanyDocument();

        self::assertInstanceOf(Collection::class, $companyDocuments);
        self::assertCount(0, $companyDocuments);
    }
}
