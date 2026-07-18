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
        if (!$schema->getTable('people_domain')->hasColumn('people_domain_id')) {
            $this->addSql('ALTER TABLE `people_domain` ADD `people_domain_id` int(11) DEFAULT NULL AFTER `theme_id`');
            $this->addSql('ALTER TABLE `people_domain` ADD KEY `people_domain_people_domain_idx` (`people_domain_id`)');
            $this->addSql('ALTER TABLE `people_domain` ADD CONSTRAINT `people_domain_people_domain_fk` FOREIGN KEY (`people_domain_id`) REFERENCES `people_domain` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }
        $this->addSql('UPDATE `people_domain` front
            LEFT JOIN (
                SELECT `people_id`, MIN(`id`) AS `people_domain_id`
                FROM `people_domain`
                WHERE `domain_type` = \'API\'
                GROUP BY `people_id`
            ) api ON api.`people_id` = front.`people_id`
            SET front.`people_domain_id` = api.`people_domain_id`
            WHERE front.`domain_type` <> \'API\'
              AND front.`people_domain_id` IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `people_domain` DROP FOREIGN KEY `people_domain_people_domain_fk`');
        $this->addSql('ALTER TABLE `people_domain` DROP INDEX `people_domain_people_domain_idx`');
        $this->addSql('ALTER TABLE `people_domain` DROP COLUMN `people_domain_id`');
    }
}
