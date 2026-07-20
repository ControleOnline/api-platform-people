<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20260717190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill legacy people image columns into people_media, align the unique key with staging, and drop the legacy columns';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('people') && $this->tableExists('people_media') && $this->tableExists('media_types')) {
            $this->assertNoPeopleMediaTypeDuplicates();
            if ($this->legacyPeopleImageColumnsExist()) {
                $this->backfillLegacyPeopleImages();
                $this->dropLegacyPeopleImageColumns();
            }

            $this->alignPeopleMediaUniqueKey();
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function backfillLegacyPeopleImages(): void
    {
        // Legacy columns were split into typed media rows; copy only the missing associations.
        $this->addSql(<<<'SQL'
INSERT INTO people_media (people_id, file_id, media_type_id)
SELECT p.id, p.image_id, mt.id
FROM people p
INNER JOIN media_types mt
    ON mt.type = CASE
        WHEN p.people_type = 'F' THEN 'avatar'
        ELSE 'logo'
    END
WHERE p.image_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1
    FROM people_media pm
    WHERE pm.people_id = p.id
      AND pm.media_type_id = mt.id
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO people_media (people_id, file_id, media_type_id)
SELECT p.id, p.background_image, mt.id
FROM people p
INNER JOIN media_types mt
    ON mt.type = 'background'
WHERE p.background_image IS NOT NULL
  AND p.people_type = 'J'
AND NOT EXISTS (
    SELECT 1
    FROM people_media pm
    WHERE pm.people_id = p.id
      AND pm.media_type_id = mt.id
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO people_media (people_id, file_id, media_type_id)
SELECT p.id, p.alternative_image, mt.id
FROM people p
INNER JOIN media_types mt
    ON mt.type = 'icon'
WHERE p.alternative_image IS NOT NULL
  AND p.people_type = 'J'
AND NOT EXISTS (
    SELECT 1
    FROM people_media pm
    WHERE pm.people_id = p.id
      AND pm.media_type_id = mt.id
)
SQL);
    }

    private function alignPeopleMediaUniqueKey(): void
    {
        $this->addSql('ALTER TABLE people_media DROP INDEX people_id_2, ADD UNIQUE KEY people_id_2 (people_id, media_type_id)');
    }

    private function dropLegacyPeopleImageColumns(): void
    {
        $this->dropForeignKeyIfExists('people', 'people_ibfk_1');
        $this->dropForeignKeyIfExists('people', 'people_ibfk_3');
        $this->dropForeignKeyIfExists('people', 'people_ibfk_4');
        $this->dropIndexIfExists('people', 'image_id');
        $this->dropIndexIfExists('people', 'alternative_image');
        $this->dropIndexIfExists('people', 'alternative_image_2');
        $this->dropColumnIfExists('people', 'image_id');
        $this->dropColumnIfExists('people', 'background_image');
        $this->dropColumnIfExists('people', 'alternative_image');
    }

    private function legacyPeopleImageColumnsExist(): bool
    {
        return $this->columnExists('people', 'image_id')
            || $this->columnExists('people', 'background_image')
            || $this->columnExists('people', 'alternative_image');
    }

    private function assertNoPeopleMediaTypeDuplicates(): void
    {
        $duplicateCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
                SELECT 1
                FROM people_media
                GROUP BY people_id, media_type_id
                HAVING COUNT(*) > 1
            ) duplicate_pairs'
        );

        if ($duplicateCount > 0) {
            throw new RuntimeException('Cannot align people_media unique key while duplicate people_id/media_type_id pairs exist.');
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$tableName, $constraintName]
        ) > 0;
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $columnName));
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP INDEX %s', $tableName, $indexName));
    }

    private function dropForeignKeyIfExists(string $tableName, string $constraintName): void
    {
        if (!$this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableName, $constraintName));
    }
}
