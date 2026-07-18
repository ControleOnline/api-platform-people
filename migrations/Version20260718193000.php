<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link people_domain entries to the API domain that serves them.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('people_domain')->hasColumn('people_domain_id')) {
            return;
        }

        $this->addSql('ALTER TABLE people_domain ADD people_domain_id INT DEFAULT NULL AFTER theme_id');
        $this->addSql('ALTER TABLE people_domain ADD CONSTRAINT people_domain_people_domain_id_fk FOREIGN KEY (people_domain_id) REFERENCES people_domain (id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX people_domain_id ON people_domain (people_domain_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE people_domain DROP FOREIGN KEY people_domain_people_domain_id_fk');
        $this->addSql('DROP INDEX people_domain_id ON people_domain');
        $this->addSql('ALTER TABLE people_domain DROP people_domain_id');
    }
}
