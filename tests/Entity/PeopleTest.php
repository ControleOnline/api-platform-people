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

    public function testSoftDeleteDefaultsFalseAndSetsDeletedAt(): void
    {
        $people = new People();
        self::assertFalse($people->isDeleted());
        self::assertNull($people->getDeletedAt());

        $people->setDeleted(true);
        self::assertTrue($people->isDeleted());
        self::assertNotNull($people->getDeletedAt());

        $people->setDeleted(false);
        self::assertFalse($people->isDeleted());
        self::assertNull($people->getDeletedAt());
    }
}
