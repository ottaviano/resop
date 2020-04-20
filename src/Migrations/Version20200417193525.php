<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200417193525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE asset_type_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE asset_type (id INT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, properties JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_68BA92E132C8A3DE ON asset_type (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX assetType_unique_org_name ON asset_type (organization_id, name)');
        $this->addSql('ALTER TABLE asset_type ADD CONSTRAINT FK_68BA92E132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE commissionable_asset ADD properties JSON NOT NULL');
        $this->addSql('ALTER TABLE commissionable_asset ADD asset_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE commissionable_asset ADD CONSTRAINT FK_4D68E555A6A2CDC5 FOREIGN KEY (asset_type_id) REFERENCES asset_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_4D68E555A6A2CDC5 ON commissionable_asset (asset_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE asset_type_id_seq CASCADE');
        $this->addSql('DROP TABLE asset_type');

        $this->addSql('ALTER TABLE commissionable_asset DROP properties');
        $this->addSql('ALTER TABLE commissionable_asset DROP CONSTRAINT FK_4D68E555A6A2CDC5');
        $this->addSql('DROP INDEX IDX_4D68E555A6A2CDC5');
        $this->addSql('ALTER TABLE commissionable_asset DROP asset_type_id');
    }
}
