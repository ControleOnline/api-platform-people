<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\PeopleLink;
use PHPUnit\Framework\TestCase;

final class PeopleLinkClosingPeriodTest extends TestCase
{
    public function testDefaults(): void
    {
        $link = new PeopleLink();
        $this->assertSame('monthly', $link->getClosingPeriod());
        $this->assertSame(0, $link->getPaymentTermDays());
    }

    public function testAcceptsValidClosingPeriods(): void
    {
        $link = new PeopleLink();
        foreach (PeopleLink::CLOSING_PERIODS as $period) {
            $link->setClosingPeriod($period);
            $this->assertSame($period, $link->getClosingPeriod());
        }
    }

    public function testInvalidClosingPeriodFallsBackToDefault(): void
    {
        $link = new PeopleLink();
        $link->setClosingPeriod('yearly');
        $this->assertSame('monthly', $link->getClosingPeriod());

        $link->setClosingPeriod('');
        $this->assertSame('monthly', $link->getClosingPeriod());

        $link->setClosingPeriod(null);
        $this->assertSame('monthly', $link->getClosingPeriod());
    }

    public function testPaymentTermDaysClampsNegativeToZero(): void
    {
        $link = new PeopleLink();
        $link->setPaymentTermDays(-5);
        $this->assertSame(0, $link->getPaymentTermDays());

        $link->setPaymentTermDays(15);
        $this->assertSame(15, $link->getPaymentTermDays());
    }

    public function testClosingPeriodNormalizedToLowercase(): void
    {
        $link = new PeopleLink();
        $link->setClosingPeriod('Weekly');
        $this->assertSame('weekly', $link->getClosingPeriod());
    }
}
