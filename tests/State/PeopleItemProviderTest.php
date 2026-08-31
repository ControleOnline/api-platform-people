<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Get;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleCompanyScopeGuard;
use ControleOnline\State\PeopleItemProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class PeopleItemProviderTest extends TestCase
{
    public function testProvideReturnsEntityAfterScopeCheck(): void
    {
        $people = $this->people(105790);

        $filters = new class {
            public function isEnabled(string $name): bool
            {
                return false;
            }
            public function disable(string $name): void {}
            public function enable(string $name): void {}
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getFilters')->willReturn($filters);
        $em->expects(self::once())
            ->method('find')
            ->with(People::class, 105790)
            ->willReturn($people);

        $scope = $this->createMock(PeopleCompanyScopeGuard::class);
        $scope->expects(self::once())->method('assertAccessible')->with(105790);

        $provider = new PeopleItemProvider($em, $scope);
        $result = $provider->provide(new Get(), ['id' => '105790']);

        self::assertSame($people, $result);
    }

    public function testProvideDeniesOutOfScope(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');

        $scope = $this->createMock(PeopleCompanyScopeGuard::class);
        $scope->method('assertAccessible')
            ->willThrowException(new AccessDeniedException('outside scope'));

        $provider = new PeopleItemProvider($em, $scope);

        $this->expectException(AccessDeniedException::class);
        $provider->provide(new Get(), ['id' => '/people/42']);
    }

    public function testProvideReturnsNullWhenMissing(): void
    {
        $filters = new class {
            public function isEnabled(string $name): bool { return false; }
            public function disable(string $name): void {}
            public function enable(string $name): void {}
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getFilters')->willReturn($filters);
        $em->method('find')->willReturn(null);

        $scope = $this->createMock(PeopleCompanyScopeGuard::class);
        $scope->expects(self::once())->method('assertAccessible')->with(999);

        $provider = new PeopleItemProvider($em, $scope);
        self::assertNull($provider->provide(new Get(), ['id' => 999]));
    }

    private function people(int $id): People
    {
        $people = new People();
        $ref = new \ReflectionProperty(People::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($people, $id);

        return $people;
    }
}
