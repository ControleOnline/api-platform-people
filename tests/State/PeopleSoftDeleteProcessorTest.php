<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Delete;
use ControleOnline\Entity\People;
use ControleOnline\State\PeopleSoftDeleteProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PeopleSoftDeleteProcessorTest extends TestCase
{
    public function testSoftDeletesPeopleOnDeleteOperation(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $people = new People();
        $processor = new PeopleSoftDeleteProcessor($em);
        $result = $processor->process($people, new Delete());

        self::assertNull($result);
        self::assertTrue($people->isDeleted());
        self::assertNotNull($people->getDeletedAt());
    }

    public function testRejectsAlreadyDeleted(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $people = new People();
        $people->setDeleted(true);

        $processor = new PeopleSoftDeleteProcessor($em);
        $this->expectException(BadRequestHttpException::class);
        $processor->process($people, new Delete());
    }
}
