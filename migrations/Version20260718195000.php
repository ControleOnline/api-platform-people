<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize the people_domain API linkage column name.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('people_domain')) {
            return;
        }

        $hasOldColumn = $this->columnExists('api_people_domain_id');
        $hasNewColumn = $this->columnExists('people_domain_id');

        if ($hasOldColumn) {
            $this->dropForeignKeyForColumn('api_people_domain_id');
            $this->dropIndexIfExists('api_people_domain_id');

            if ($hasNewColumn) {
                $this->addSql('UPDATE `people_domain` SET `people_domain_id` = COALESCE(`people_domain_id`, `api_people_domain_id`)');
                $this->addSql('ALTER TABLE `people_domain` DROP COLUMN `api_people_domain_id`');
            } else {
                $this->addSql('ALTER TABLE `people_domain` CHANGE `api_people_domain_id` `people_domain_id` int(11) DEFAULT NULL');
                $hasNewColumn = true;
            }
        }

        if (!$hasNewColumn) {
            $this->addSql('ALTER TABLE `people_domain` ADD `people_domain_id` int(11) DEFAULT NULL AFTER `theme_id`');
        }

        if (!$this->hasIndexForColumn('people_domain_id')) {
            $this->addSql('ALTER TABLE `people_domain` ADD KEY `people_domain_id` (`people_domain_id`)');
        }

        if (!$this->foreignKeyExistsForColumn('people_domain_id')) {
            $this->addSql('ALTER TABLE `people_domain` ADD CONSTRAINT `people_domain_people_domain_id_fk` FOREIGN KEY (`people_domain_id`) REFERENCES `people_domain` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function columnExists(string $column): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name',
            [
                'table_name' => 'people_domain',
                'column_name' => $column,
            ]
        ) > 0;
    }

    private function tableExists(string $table): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name',
            [
                'table_name' => $table,
            ]
        ) > 0;
    }

    private function foreignKeyExistsForColumn(string $column): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [
                'table_name' => 'people_domain',
                'column_name' => $column,
            ]
        ) > 0;
    }

    private function dropForeignKeyForColumn(string $column): void
    {
        $foreignKey = $this->connection->fetchOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [
                'table_name' => 'people_domain',
                'column_name' => $column,
            ]
        );

        if (is_string($foreignKey) && trim($foreignKey) !== '') {
            $this->addSql(sprintf('ALTER TABLE `people_domain` DROP FOREIGN KEY `%s`', str_replace('`', '``', $foreignKey)));
        }
    }

    private function hasIndexForColumn(string $column): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name',
            [
                'table_name' => 'people_domain',
                'column_name' => $column,
            ]
        ) > 0;
    }

    private function dropIndexIfExists(string $index): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND INDEX_NAME = :index_name',
            [
                'table_name' => 'people_domain',
                'index_name' => $index,
            ]
        ) > 0;

        if ($exists) {
            $this->addSql(sprintf('ALTER TABLE `people_domain` DROP INDEX `%s`', str_replace('`', '``', $index)));
        }
    }
}
