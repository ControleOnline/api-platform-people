<?php

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DocumentFileTest extends TestCase
{
    public function testDocumentFileEntityExposesDocumentAndFileAccessors(): void
    {
        $reflection = new ReflectionClass(DocumentFile::class);
        $this->assertTrue($reflection->hasMethod('getDocument'));
        $this->assertTrue($reflection->hasMethod('setDocument'));
        $this->assertTrue($reflection->hasMethod('getFile'));
        $this->assertTrue($reflection->hasMethod('setFile'));
        $this->assertTrue($reflection->hasMethod('getId'));
    }

    public function testDocumentHoldsDocumentFilesCollection(): void
    {
        $document = new Document();
        $this->assertCount(0, $document->getDocumentFiles());

        $attachment = new DocumentFile();
        $document->addDocumentFile($attachment);

        $this->assertCount(1, $document->getDocumentFiles());
        $this->assertSame($document, $attachment->getDocument());

        $document->removeDocumentFile($attachment);
        $this->assertCount(0, $document->getDocumentFiles());
    }
}
