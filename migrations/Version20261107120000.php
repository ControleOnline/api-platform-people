<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * app-community#1392: document_file.file_id was created with a FK to a
 * non-existent `file` table (the real table is `files`), so every insert
 * failed with HTTP 500 and uploaded documents were never attached.
 */
final class Version20261107120000 extends AbstractMigration
{
    private const FK = 'document_file_ibfk_file';

    public function getDescription(): string
    {
        return 'Repoint document_file.file_id foreign key to the files table';
    }

    public function up(Schema $schema): void
    {
        $referenced = $this->connection->fetchOne(
            'SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            ['document_file', self::FK]
        );

        if ($referenced === 'files') {
            return;
        }

        if ($referenced !== false && $referenced !== null) {
            $this->addSql('ALTER TABLE `document_file` DROP FOREIGN KEY `' . self::FK . '`');
        }

        // Rows pointing to missing files cannot satisfy the constraint.
        $this->addSql('DELETE FROM `document_file` WHERE `file_id` NOT IN (SELECT `id` FROM `files`)');
        $this->addSql(
            'ALTER TABLE `document_file` ADD CONSTRAINT `' . self::FK . '`
             FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        // Intentionally a no-op: the previous constraint referenced a table that does not exist.
    }
}
