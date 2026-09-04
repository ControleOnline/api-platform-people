<?php

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\Document;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DocumentVehicleCompatibilityTest extends TestCase
{
    public function testDocumentExposesVehiclePropertyForStaleDoctrineMetadata(): void
    {
        $reflection = new ReflectionClass(Document::class);

        $this->assertTrue(
            $reflection->hasProperty('vehicle'),
            'Document::$vehicle must exist so ClassMetadata::wakeupReflection does not throw'
        );
        $this->assertTrue($reflection->hasMethod('getVehicle'));
        $this->assertTrue($reflection->hasMethod('setVehicle'));

        $document = new Document();
        $this->assertNull($document->getVehicle());
        $this->assertSame($document, $document->setVehicle(null));
        $this->assertNull($document->getVehicle());
    }
}
