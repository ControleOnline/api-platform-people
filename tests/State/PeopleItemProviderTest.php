<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Get;
use ControleOnline\Entity\People;
use ControleOnline\State\PeopleItemProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PeopleItemProviderTest extends TestCase
{
    public function testProvideReturnsEntityByNumericId(): void
    {
        $people = new People();
        $ref = new \ReflectionProperty(People::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($people, 105790);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(People::class, 105790)
            ->willReturn($people);

        $provider = new PeopleItemProvider($em);
        $result = $provider->provide(new Get(), ['id' => '105790']);

        self::assertSame($people, $result);
    }

    public function testProvideAcceptsIriStyleId(): void
    {
        $people = new People();
        $ref = new \ReflectionProperty(People::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($people, 42);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->with(People::class, 42)->willReturn($people);

        $provider = new PeopleItemProvider($em);
        self::assertSame($people, $provider->provide(new Get(), ['id' => '/people/42']));
    }

    public function testProvideReturnsNullWhenMissing(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);

        $provider = new PeopleItemProvider($em);
        self::assertNull($provider->provide(new Get(), ['id' => 999]));
    }
}
