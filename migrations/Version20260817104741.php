<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Align people table with entity after removal of image / alternative_image / background_image
 * mappings (see a650536). Production reported 500 when code expected image_id but column
 * was already absent (or the inverse: stale mapping vs schema). This migration drops the
 * obsolete columns and FKs if they still exist, so schema matches the current entity.
 */
final class Version20260817104741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop obsolete people.image_id, alternative_image, background_image columns if present.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('people')) {
            return;
        }

        // Drop FKs that may reference these columns before dropping columns.
        $this->dropForeignKeyIfExists('people', 'FK_people_image');
        $this->dropForeignKeyIfExists('people', 'FK_people_image_id');
        $this->dropForeignKeyIfExists('people', 'FK_PEOPLE_IMAGE');
        $this->dropForeignKeyIfExists('people', 'people_ibfk_image');
        $this->dropForeignKeyIfExists('people', 'FK_people_alternative_image');
        $this->dropForeignKeyIfExists('people', 'FK_people_background_image');
        $this->dropForeignKeyIfExists('people', 'FK_people_background');

        // Discover actual FK names that reference these columns (MySQL).
        $this->dropForeignKeysOnColumns('people', ['image_id', 'alternative_image', 'background_image']);

        $this->dropIndexIfExists('people', 'image_id');
        $this->dropIndexIfExists('people', 'IDX_people_image_id');
        $this->dropIndexIfExists('people', 'alternative_image');
        $this->dropIndexIfExists('people', 'background_image');
        $this->dropIndexIfExists('people', 'IDX_people_alternative_image');
        $this->dropIndexIfExists('people', 'IDX_people_background_image');

        $this->dropColumnIfExists('people', 'image_id');
        $this->dropColumnIfExists('people', 'alternative_image');
        $this->dropColumnIfExists('people', 'background_image');
    }

    public function down(Schema $schema): void
    {
        // Intentionally irreversible: columns were removed from the domain model.
        return;
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

        $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $tableName, $columnName));
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
    }

    private function dropForeignKeyIfExists(string $tableName, string $constraintName): void
    {
        if (!$this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $tableName, $constraintName));
    }

    /**
     * Drop any FK whose COLUMN_NAME is in the given list (handles unknown constraint names).
     */
    private function dropForeignKeysOnColumns(string $tableName, array $columnNames): void
    {
        if ($columnNames === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($columnNames), '?'));
        $params = array_merge([$tableName], $columnNames);

        $rows = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME IN ($placeholders)
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            $params
        );

        foreach ($rows as $row) {
            $name = $row['CONSTRAINT_NAME'] ?? null;
            if (is_string($name) && $name !== '') {
                $this->addSql(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $tableName, $name));
            }
        }
    }
}
