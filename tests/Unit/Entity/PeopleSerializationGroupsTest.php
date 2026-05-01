<?php

namespace ControleOnline\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;

class PeopleSerializationGroupsTest extends TestCase
{
    public function testPeopleBroadReadNoLongerExposesNestedUsers(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../src/Entity/People.php');

        self::assertIsString($source);
        self::assertMatchesRegularExpression('/private \$user;/', $source);
        self::assertMatchesRegularExpression('/#\[Groups\(\[(?:(?!people:read).)*people:write(?:(?!people:read).)*\]\)\]\s*private \$user;/s', $source);
        self::assertDoesNotMatchRegularExpression('/#\[Groups\(\[(?:(?!\]\)\]).)*people:read(?:(?!\]\)\]).)*\]\)\]\s*private \$user;/s', $source);
    }
}
