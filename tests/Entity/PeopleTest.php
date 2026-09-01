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
        self::assertInstanceOf(Collection::class, $people->getCompanyDocument());
        self::assertCount(0, $people->getCompanyDocument());
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

    public function testNameAndAliasPreserveStoredCapitalization(): void
    {
        $people = new People();
        $people->setName('Maria Silva');
        $people->setAlias('Mari');

        self::assertSame('Maria Silva', $people->getName());
        self::assertSame('Mari', $people->getAlias());
    }

}
