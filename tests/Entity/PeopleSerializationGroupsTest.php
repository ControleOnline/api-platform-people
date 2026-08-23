<?php

namespace ControleOnline\Tests\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Broad people:read must not serialize nested User (credentials leak vector).
 * Sensitive user fields stay on the dedicated users resource / guarded path.
 */
class PeopleSerializationGroupsTest extends TestCase
{
    public function testPeopleBroadReadNoLongerExposesNestedUsers(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/Entity/People.php');

        self::assertIsString($source);
        self::assertMatchesRegularExpression('/private \$user;/', $source);
        // $user Groups must include people:write and must not include people:read
        self::assertMatchesRegularExpression(
            '/#\[Groups\(\[(?:(?!people:read).)*people:write(?:(?!people:read).)*\]\)\]\s*private \$user;/s',
            $source
        );
        self::assertDoesNotMatchRegularExpression(
            '/#\[Groups\(\[(?:(?!\]\)\]).)*people:read(?:(?!\]\)\]).)*\]\)\]\s*private \$user;/s',
            $source
        );
    }
}
