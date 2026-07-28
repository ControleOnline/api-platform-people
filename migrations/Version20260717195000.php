<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the API domain linkage to people_domain and seed the current company mappings.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('people_domain')) {
            return;
        }

        if (!$this->columnExists('people_domain_id')) {
            $this->addSql('ALTER TABLE `people_domain` ADD `people_domain_id` int(11) DEFAULT NULL AFTER `theme_id`');
            $this->addSql('ALTER TABLE `people_domain` ADD KEY `people_domain_id` (`people_domain_id`)');
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
}
