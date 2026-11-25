<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates marketing_event where it is missing. Version20260820080000 was written
 * under the namespace ControleOnline\Migrations, which is not a registered
 * migrations path, so it never ran on tenant databases (GET/POST
 * /marketing_events answered 500 and Version20261122220000 failed with
 * "Table marketing_event doesn't exist"). Runs right before the gclid
 * migration and is idempotent across tenants.
 */
final class Version20261122210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create marketing_event when missing (original migration had an unregistered namespace)';
    }

    public function up(Schema $schema): void
    {
        if ($this->hasTable('marketing_event')) {
            return;
        }

        $this->addSql('CREATE TABLE marketing_event (
            id INT AUTO_INCREMENT NOT NULL,
            people_id INT DEFAULT NULL,
            event_name VARCHAR(64) NOT NULL,
            event_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            page_url VARCHAR(2048) DEFAULT NULL,
            visitor_id VARCHAR(64) NOT NULL,
            lead_id VARCHAR(64) DEFAULT NULL,
            utm_source VARCHAR(255) DEFAULT NULL,
            utm_medium VARCHAR(255) DEFAULT NULL,
            utm_campaign VARCHAR(255) DEFAULT NULL,
            utm_term VARCHAR(255) DEFAULT NULL,
            utm_content VARCHAR(255) DEFAULT NULL,
            referrer VARCHAR(2048) DEFAULT NULL,
            gclid VARCHAR(255) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            idempotency_key VARCHAR(64) NOT NULL,
            payload_hash VARCHAR(64) DEFAULT NULL,
            creation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX marketing_event_idempotency_idx (idempotency_key),
            INDEX marketing_event_visitor_idx (visitor_id),
            INDEX marketing_event_event_name_idx (event_name),
            INDEX marketing_event_event_at_idx (event_at),
            INDEX marketing_event_people_idx (people_id),
            INDEX marketing_event_gclid_idx (gclid),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        if ($this->hasTable('people')) {
            $this->addSql('ALTER TABLE marketing_event ADD CONSTRAINT FK_MARKETING_EVENT_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // The table may predate this migration; never drop tracked events here.
    }

    private function hasTable(string $table): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        ) > 0;
    }
}
