<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261017072656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Devices: fb_devices_module_history_samples and fb_devices_module_history_rollups, the property value history (epoch-second times, no foreign key)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fb_devices_module_history_rollups (property_id BINARY(16) NOT NULL, resolution INT UNSIGNED NOT NULL, bucket_start INT UNSIGNED NOT NULL, value_min DOUBLE PRECISION DEFAULT NULL, value_max DOUBLE PRECISION DEFAULT NULL, value_avg DOUBLE PRECISION DEFAULT NULL, value_last DOUBLE PRECISION DEFAULT NULL, samples INT UNSIGNED NOT NULL, PRIMARY KEY (property_id, resolution, bucket_start)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` COMMENT = \'Property value history rollups\' ');
        $this->addSql('CREATE TABLE fb_devices_module_history_samples (property_id BINARY(16) NOT NULL, sampled_at INT UNSIGNED NOT NULL, value DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (property_id, sampled_at)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` COMMENT = \'Property value history\' ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE fb_devices_module_history_rollups');
        $this->addSql('DROP TABLE fb_devices_module_history_samples');
    }
}
