<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Delete;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\State\PeopleSoftDeleteProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PeopleSoftDeleteProcessorTest extends TestCase
{
    public function testSoftDeletesPeopleAndDisablesPeopleLinks(): void
    {
        $people = new People();
        $linkEnabled = $this->createMock(PeopleLink::class);
        $linkEnabled->method('getEnabled')->willReturn(1);
        $linkEnabled->expects($this->once())->method('setEnabled')->with(0);

        $linkAlreadyDisabled = $this->createMock(PeopleLink::class);
        $linkAlreadyDisabled->method('getEnabled')->willReturn(0);
        $linkAlreadyDisabled->expects($this->never())->method('setEnabled');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->with(['people' => $people])->willReturn([$linkEnabled, $linkAlreadyDisabled]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(PeopleLink::class)->willReturn($repo);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->once())->method('flush');

        $result = (new PeopleSoftDeleteProcessor($em))->process($people, new Delete());

        self::assertNull($result);
        self::assertTrue($people->isDeleted());
        self::assertNotNull($people->getDeletedAt());
    }

    public function testRejectsAlreadyDeleted(): void
    {
        $people = new People();
        $people->setDeleted(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $this->expectException(BadRequestHttpException::class);
        (new PeopleSoftDeleteProcessor($em))->process($people, new Delete());
    }
}
