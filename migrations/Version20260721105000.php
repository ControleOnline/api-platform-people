<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721105000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pin media type for company map markers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO media_types (type, people_type)
             SELECT 'pin', 'J'
             WHERE NOT EXISTS (
                 SELECT 1 FROM media_types WHERE type = 'pin'
             )"
        );
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
