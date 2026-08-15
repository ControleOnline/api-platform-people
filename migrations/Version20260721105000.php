<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721105000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move the pin media type seed to the final tenant seed migration.';
    }

    public function up(Schema $schema): void
    {
        return;
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
