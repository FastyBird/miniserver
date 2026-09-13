<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260913085735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow one device, channel or connector property to back data sources on several widgets';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_channels_data_sources DROP INDEX UNIQ_8D605470BD63C31B, ADD INDEX IDX_8D605470BD63C31B (data_source_property)');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_connectors_data_sources DROP INDEX UNIQ_3AC0FC69BD63C31B, ADD INDEX IDX_3AC0FC69BD63C31B (data_source_property)');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_devices_data_sources DROP INDEX UNIQ_99741E8BBD63C31B, ADD INDEX IDX_99741E8BBD63C31B (data_source_property)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_devices_data_sources DROP INDEX IDX_99741E8BBD63C31B, ADD UNIQUE INDEX UNIQ_99741E8BBD63C31B (data_source_property)');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_connectors_data_sources DROP INDEX IDX_3AC0FC69BD63C31B, ADD UNIQUE INDEX UNIQ_3AC0FC69BD63C31B (data_source_property)');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_channels_data_sources DROP INDEX IDX_8D605470BD63C31B, ADD UNIQUE INDEX UNIQ_8D605470BD63C31B (data_source_property)');
    }
}
