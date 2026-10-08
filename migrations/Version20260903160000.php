<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow documents to belong to people or vehicles and scope document types by locality';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE document MODIFY people_id INT(11) DEFAULT NULL");
        $this->addSql("ALTER TABLE document ADD vehicle_id INT(11) DEFAULT NULL AFTER people_id");
        $this->addSql("ALTER TABLE document ADD INDEX document_vehicle_idx (vehicle_id)");
        $this->addSql("ALTER TABLE document ADD CONSTRAINT document_vehicle_fk FOREIGN KEY (vehicle_id) REFERENCES delivery_courier_vehicle (id) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->addSql("ALTER TABLE document_type ADD owner_type VARCHAR(20) NOT NULL DEFAULT 'people' AFTER document_type");
        $this->addSql("ALTER TABLE document_type ADD state_id INT(11) DEFAULT NULL AFTER owner_type");
        $this->addSql("ALTER TABLE document_type ADD city_id INT(11) DEFAULT NULL AFTER state_id");
        $this->addSql("ALTER TABLE document_type ADD INDEX document_type_state_idx (state_id)");
        $this->addSql("ALTER TABLE document_type ADD INDEX document_type_city_idx (city_id)");
        $this->addSql("ALTER TABLE document_type ADD CONSTRAINT document_type_state_fk FOREIGN KEY (state_id) REFERENCES state (id) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->addSql("ALTER TABLE document_type ADD CONSTRAINT document_type_city_fk FOREIGN KEY (city_id) REFERENCES city (id) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY document_vehicle_fk');
        $this->addSql('ALTER TABLE document DROP INDEX document_vehicle_idx');
        $this->addSql('ALTER TABLE document DROP vehicle_id');
        $this->addSql('ALTER TABLE document MODIFY people_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE document_type DROP FOREIGN KEY document_type_city_fk');
        $this->addSql('ALTER TABLE document_type DROP FOREIGN KEY document_type_state_fk');
        $this->addSql('ALTER TABLE document_type DROP INDEX document_type_city_idx');
        $this->addSql('ALTER TABLE document_type DROP INDEX document_type_state_idx');
        $this->addSql('ALTER TABLE document_type DROP city_id, DROP state_id, DROP owner_type');
    }
}
