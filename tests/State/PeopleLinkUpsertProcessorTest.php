<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Post;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Repository\PeopleLinkRepository;
use ControleOnline\Service\PeopleLinkService;
use ControleOnline\State\PeopleLinkUpsertProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PeopleLinkUpsertProcessorTest extends TestCase
{
    public function testCreatesWhenNoExistingLink(): void
    {
        $incoming = $this->link(null, $this->people(5), $this->people(9), 'franchisee');

        $repo = $this->createMock(PeopleLinkRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(PeopleLink::class)->willReturn($repo);
        $em->expects($this->once())->method('persist')->with($incoming);
        $em->expects($this->once())->method('flush');

        $service = $this->createMock(PeopleLinkService::class);
        $service->expects($this->once())->method('prePersist')->with($incoming);

        $processor = new PeopleLinkUpsertProcessor($em, $service);
        $result = $processor->process($incoming, new Post());

        $this->assertSame($incoming, $result);
    }

    public function testReusesExistingLinkAndCallsPreUpdate(): void
    {
        $company = $this->people(5);
        $people = $this->people(9);
        $incoming = $this->link(null, $company, $people, 'franchisee');
        $incoming->setComission(12.5);
        $existing = $this->link(77, $company, $people, 'franchisee');

        $repo = $this->createMock(PeopleLinkRepository::class);
        $repo->method('findOneBy')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(PeopleLink::class)->willReturn($repo);
        $em->method('contains')->with($incoming)->willReturn(false);
        $em->expects($this->once())->method('persist')->with($existing);
        $em->expects($this->once())->method('flush');

        $service = $this->createMock(PeopleLinkService::class);
        $service->expects($this->once())->method('preUpdate')->with($existing);
        $service->expects($this->never())->method('prePersist');

        $processor = new PeopleLinkUpsertProcessor($em, $service);
        $result = $processor->process($incoming, new Post());

        $this->assertSame($existing, $result);
        $this->assertSame(12.5, $existing->getComission());
    }

    private function people(int $id): People
    {
        $people = new People();
        $ref = new \ReflectionProperty(People::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($people, $id);

        return $people;
    }

    private function link(?int $id, People $company, People $people, string $type): PeopleLink
    {
        $link = new PeopleLink();
        if ($id !== null) {
            $ref = new \ReflectionProperty(PeopleLink::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($link, $id);
        }
        $link->setCompany($company);
        $link->setPeople($people);
        $link->setLinkType($type);

        return $link;
    }
}
