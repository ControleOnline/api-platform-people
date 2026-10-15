<?php

declare(strict_types=1);

namespace ControleOnline\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add people_domain.app_type and backfill from domain host patterns.
 */
final class Version20261015010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add people_domain.app_type and backfill operational type from domain';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE people_domain ADD app_type VARCHAR(64) NOT NULL DEFAULT 'MANAGER'");

        $rules = [
            ["domain LIKE 'admin.%' OR domain LIKE '%.admin.%' OR domain = 'admin.controleonline.com'", 'ADMIN'],
            ["domain LIKE 'checkout.%' OR domain LIKE '%.checkout.%' OR domain = 'checkout.controleonline.com'", 'CHECKOUT'],
            ["domain LIKE 'crm.%' OR domain LIKE '%.crm.%' OR domain = 'crm.controleonline.com'", 'CRM'],
            ["domain LIKE 'delivery.%' OR domain LIKE '%.delivery.%' OR domain = 'delivery.controleonline.com'", 'DELIVERY'],
            ["domain LIKE 'menu.%' OR domain LIKE '%.menu.%' OR domain = 'menu.controleonline.com'", 'MENU'],
            ["domain LIKE 'pos.%' OR domain LIKE '%.pos.%' OR domain = 'pos.controleonline.com'", 'POS'],
            ["domain LIKE 'ppc.%' OR domain LIKE '%.ppc.%' OR domain = 'ppc.controleonline.com' OR domain LIKE 'display.%'", 'PPC'],
            ["domain LIKE 'service.%' OR domain LIKE '%.service.%' OR domain = 'service.controleonline.com'", 'SERVICE'],
            ["domain LIKE 'shop.%' OR domain LIKE '%.shop.%' OR domain = 'shop.controleonline.com' OR domain_type = 'SHOP'", 'SHOP'],
            ["domain LIKE 'manager.%' OR domain LIKE '%.manager.%' OR domain = 'manager.controleonline.com' OR domain = 'app.controleonline.com' OR domain LIKE 'app.%'", 'MANAGER'],
            ["domain LIKE 'api.%' OR domain_type = 'API'", 'MANAGER'],
        ];

        foreach ($rules as [$where, $appType]) {
            $this->addSql(sprintf(
                "UPDATE people_domain SET app_type = '%s' WHERE %s",
                $appType,
                $where
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE people_domain DROP COLUMN app_type');
    }
}
