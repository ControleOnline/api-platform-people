<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create people_category table for person classification (profession/position/sector/activity_branch) with timeline';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `people_company_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_people_category_people` (`people_id`),
  KEY `idx_people_category_category` (`category_id`),
  KEY `idx_people_category_company` (`people_company_id`),
  KEY `idx_people_category_dates` (`start_date`, `end_date`),
  CONSTRAINT `people_category_ibfk_people` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_category_ibfk_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_category_ibfk_company` FOREIGN KEY (`people_company_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS `people_category`');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
