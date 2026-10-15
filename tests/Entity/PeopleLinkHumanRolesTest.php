<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\PeopleLink;
use PHPUnit\Framework\TestCase;

/**
 * Contract: HUMAN_LINK and API_ROLE_MAP include salesman + after-sales (#446).
 */
final class PeopleLinkHumanRolesTest extends TestCase
{
    public function testHumanLinkIncludesSalesmanAndAfterSales(): void
    {
        self::assertContains('salesman', PeopleLink::HUMAN_LINK);
        self::assertContains('after-sales', PeopleLink::HUMAN_LINK);
        self::assertContains('courier', PeopleLink::HUMAN_LINK);
        self::assertContains('employee', PeopleLink::HUMAN_LINK);
    }

    public function testApiRoleMapMapsSalesmanAndAfterSales(): void
    {
        self::assertSame('ROLE_SALESMAN', PeopleLink::API_ROLE_MAP['salesman']);
        self::assertSame('ROLE_AFTER_SALES', PeopleLink::API_ROLE_MAP['after-sales']);
        self::assertSame('ROLE_SALESMAN', PeopleLink::toRole('salesman'));
        self::assertSame('ROLE_AFTER_SALES', PeopleLink::toRole('after-sales'));
    }
}
