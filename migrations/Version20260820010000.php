<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * api-platform-people#12 — people_link closing_period + payment_term_days
 * for commission/royalty invoice aggregation windows.
 */
final class Version20260820010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add people_link.closing_period and people_link.payment_term_days (commission/royalty closing)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `people_link`
            ADD COLUMN `closing_period` VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER `minimum_comission`,
            ADD COLUMN `payment_term_days` INT NOT NULL DEFAULT 0 AFTER `closing_period`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `people_link`
            DROP COLUMN `closing_period`,
            DROP COLUMN `payment_term_days`');
    }
}
