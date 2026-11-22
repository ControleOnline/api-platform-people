<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * #67 — add gclid column to marketing_event for Google Ads offline attribution.
 * Nullable so existing rows (and non-ad traffic) stay valid.
 */
final class Version20261122220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gclid column to marketing_event for Google Ads offline conversion attribution';
    }

    public function up(Schema $schema): void
    {
        $existing = $this->connection->fetchOne(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marketing_event' AND COLUMN_NAME = 'gclid'"
        );

        if ($existing === false || $existing === null) {
            $this->addSql('ALTER TABLE marketing_event ADD COLUMN gclid VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE INDEX marketing_event_gclid_idx ON marketing_event (gclid)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketing_event DROP INDEX marketing_event_gclid_idx');
        $this->addSql('ALTER TABLE marketing_event DROP COLUMN gclid');
    }
}
