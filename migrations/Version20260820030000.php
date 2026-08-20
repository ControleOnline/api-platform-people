<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Soft-delete for people (PF/PJ): deleted flag + deleted_at.
 * Physical DELETE is never used for operational removal.
 */
final class Version20260820030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add people.deleted (bool default false) and people.deleted_at for soft-delete of PF/PJ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE people ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE people ADD COLUMN deleted_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_people_deleted ON people (deleted)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_people_deleted ON people');
        $this->addSql('ALTER TABLE people DROP COLUMN deleted_at');
        $this->addSql('ALTER TABLE people DROP COLUMN deleted');
    }
}
