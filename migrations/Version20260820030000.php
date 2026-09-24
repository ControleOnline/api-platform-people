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
        $people = $schema->getTable('people');
        if (!$people->hasColumn('deleted')) {
            $this->addSql('ALTER TABLE people ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$people->hasColumn('deleted_at')) {
            $this->addSql('ALTER TABLE people ADD COLUMN deleted_at DATETIME DEFAULT NULL');
        }
        if (!$people->hasIndex('idx_people_deleted')) {
            $this->addSql('CREATE INDEX idx_people_deleted ON people (deleted)');
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
