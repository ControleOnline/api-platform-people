<?php

namespace ControleOnline\Tests\State;

use ApiPlatform\Metadata\Put;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleCompanyScopeGuard;
use ControleOnline\State\PeopleCadastralUpdateProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class PeopleCadastralUpdateProcessorTest extends TestCase
{
    public function testProcessUpdatesNameWhenInScope(): void
    {
        $people = $this->people(105790);
        $people->setName('Old');

        $filters = new class {
            public function isEnabled(string $name): bool { return false; }
            public function disable(string $name): void {}
            public function enable(string $name): void {}
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getFilters')->willReturn($filters);
        $em->method('find')->with(People::class, 105790)->willReturn($people);
        $em->expects(self::once())->method('persist')->with($people);
        $em->expects(self::once())->method('flush');

        $scope = $this->createMock(PeopleCompanyScopeGuard::class);
        $scope->expects(self::once())->method('assertAccessible')->with(105790);

        $processor = new PeopleCadastralUpdateProcessor($em, $scope);
        $request = Request::create('/people/105790', 'PUT', [], [], [], [], json_encode([
            'name' => 'Novo Nome',
            'alias' => 'Novo',
        ]));

        $result = $processor->process(new People(), new Put(), ['id' => '105790'], ['request' => $request]);

        self::assertSame('Novo Nome', $result->getName());
        self::assertSame('Novo', $result->getAlias());
    }

    public function testProcessDeniesOutOfScopeWithoutWrite(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $scope = $this->createMock(PeopleCompanyScopeGuard::class);
        $scope->method('assertAccessible')
            ->willThrowException(new AccessDeniedException('outside scope'));

        $processor = new PeopleCadastralUpdateProcessor($em, $scope);

        $this->expectException(AccessDeniedException::class);
        $processor->process(new People(), new Put(), ['id' => '105790'], []);
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
