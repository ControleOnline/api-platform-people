<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * api-community#83 — people.deleted / people.deleted_at for soft-delete.
 * Namespace MUST stay DoctrineMigrations\People so doctrine_migrations.yaml
 * discovers this file (ControleOnline\Migrations in this folder is invisible).
 */
final class Version20260820030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add people.deleted (bool default false) and people.deleted_at for soft-delete of PF/PJ';
    }

    public function up(Schema $schema): void
    {
        // The legacy people table contains MySQL enum/set columns. DBAL 4 can
        // fail while introspecting those columns, even though these statements
        // only add ordinary columns. Query information_schema instead.
        if (!$this->columnExists('people', 'deleted')) {
            $this->addSql('ALTER TABLE people ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$this->columnExists('people', 'deleted_at')) {
            $this->addSql('ALTER TABLE people ADD COLUMN deleted_at DATETIME DEFAULT NULL');
        }
        if (!$this->indexExists('people', 'idx_people_deleted')) {
            $this->addSql('CREATE INDEX idx_people_deleted ON people (deleted)');
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1',
            [$tableName, $columnName]
        );
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$tableName, $indexName]
        );
    }
}
