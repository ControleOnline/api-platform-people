<?php

namespace ControleOnline\Tests\Entity;

use PHPUnit\Framework\TestCase;

class PublicSignupRouteTest extends TestCase
{
    public function testLegacyUsersCreateAccountRouteUsesCurrentPublicSignupController(): void
    {
        $peopleResource = file_get_contents(dirname(__DIR__, 2) . '/src/Entity/People.php');

        self::assertIsString($peopleResource);
        self::assertStringContainsString("uriTemplate: '/create-account'", $peopleResource);
        self::assertStringContainsString("uriTemplate: '/users/create-account'", $peopleResource);
        self::assertSame(
            2,
            substr_count($peopleResource, 'controller: CreateAccountAction::class')
        );
    }

    public function testLegacyUsersCreateAccountRouteIsPublicInMainSecurityConfig(): void
    {
        $securityConfig = file_get_contents(
            dirname(__DIR__, 5) . '/config/packages/security.yaml'
        );

        self::assertIsString($securityConfig);
        self::assertStringContainsString(
            '- { path: ^/users/create-account$, roles: PUBLIC_ACCESS }',
            $securityConfig
        );
    }
}
