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

    /**
     * Regression for api-community#58: entity must not map obsolete image_id /
     * alternative_image / background_image columns (removed in a650536).
     * Presence of those accessors would reintroduce the production 500 when the
     * column is absent from the schema.
     */
    public function testPeopleDoesNotExposeObsoleteImageAccessors(): void
    {
        $people = new People();

        self::assertFalse(method_exists($people, 'getImage'));
        self::assertFalse(method_exists($people, 'setImage'));
        self::assertFalse(method_exists($people, 'getAlternativeImage'));
        self::assertFalse(method_exists($people, 'setAlternativeImage'));
        self::assertFalse(method_exists($people, 'getBackground'));
        self::assertFalse(method_exists($people, 'setBackground'));

        $ref = new \ReflectionClass(People::class);
        self::assertFalse($ref->hasProperty('image'));
        self::assertFalse($ref->hasProperty('alternative_image'));
        self::assertFalse($ref->hasProperty('background'));
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

