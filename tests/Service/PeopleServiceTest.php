<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Entity/People.php';
require_once dirname(__DIR__, 2) . '/src/Entity/PeopleLink.php';
require_once dirname(__DIR__, 2) . '/src/Service/PeopleRoleService.php';
require_once dirname(__DIR__, 2) . '/src/Service/PeopleService.php';

use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PeopleServiceTest extends TestCase
{
    public function testCommercialAccessJoinConditionBridgesHumanContactToAccessibleCompany(): void
    {
        $service = $this->buildService();

        $condition = $this->invokePrivateMethod(
            $service,
            'getCommercialAccessJoinCondition',
            ['PeopleLink', 'PeopleCompanyLink']
        );

        self::assertSame(
            'PeopleCompanyLink.people = PeopleLink.company AND PeopleCompanyLink.enable = :commercialLinkEnabled AND PeopleCompanyLink.linkType IN(:panelLinkTypes)',
            $condition
        );
    }

    public function testBuildPeopleVisibilityConditionsIncludesCommercialCompanyPath(): void
    {
        $service = $this->buildService();
        $currentPeople = $this->createConfiguredMock(People::class, [
            'getId' => 6,
        ]);

        $conditions = $this->invokePrivateMethod(
            $service,
            'buildPeopleVisibilityConditions',
            [
                $currentPeople,
                [11],
                'people',
                'PeopleLink',
                'PeopleCompanyLink',
            ]
        );

        self::assertContains('PeopleCompanyLink.company IN(:myCompanies)', $conditions);
        self::assertContains('PeopleLink.people = :myPeopleId', $conditions);
        self::assertContains('people.id IN(:myCompanies)', $conditions);
    }

    private function buildService(): PeopleService
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(TokenStorageInterface::class);
        $requestStack = new RequestStack();
        $roles = $this->createMock(PeopleRoleService::class);

        return new PeopleService($manager, $security, $roles, $requestStack);
    }

    private function invokePrivateMethod(object $instance, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionClass($instance);
        $reflectedMethod = $reflection->getMethod($method);
        $reflectedMethod->setAccessible(true);

        return $reflectedMethod->invokeArgs($instance, $arguments);
    }
}
