<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create document_file table for multiple attachments per person document (app-community#139)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `document_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_file_unique` (`document_id`, `file_id`),
  KEY `document_id` (`document_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `document_file_ibfk_document` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `document_file_ibfk_file` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS `document_file`');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
