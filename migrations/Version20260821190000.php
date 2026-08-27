<?php

declare(strict_types=1);

namespace ControleOnline\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Extend people_link.link_type SET with filial (branch) for PJ→PJ links.
 */
final class Version20260821190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add filial to people_link.link_type SET (PJ→PJ branch links alongside franchisee)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE people_link MODIFY link_type SET('prospect','employee','client','provider','franchisee','filial','professor','family','salesman','owner','sellers-client','director','manager','admin','courier') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE people_link MODIFY link_type SET('prospect','employee','client','provider','franchisee','professor','family','salesman','owner','sellers-client','director','manager','admin','courier') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL");
    }
}
