<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260917084547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the (DC2Type:...) column comments DBAL 4 no longer writes or reads';
    }

    public function up(Schema $schema): void
    {
        // Comment-only. DBAL 4 stopped emitting the (DC2Type:...) hints it used to need for
        // diffing, so introspection reports every commented column as changed until they are
        // removed; without this, migrations:diff produces this same file on every run.
        // No column type, length or nullability changes -- verified against the generated SQL.
        $this->addSql('ALTER TABLE fb_accounts_module_accounts CHANGE account_id account_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_accounts_module_accounts_details CHANGE detail_id detail_id BINARY(16) NOT NULL, CHANGE account_id account_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_accounts_module_emails CHANGE email_id email_id BINARY(16) NOT NULL, CHANGE account_id account_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_accounts_module_identities CHANGE identity_id identity_id BINARY(16) NOT NULL, CHANGE account_id account_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_channels CHANGE channel_id channel_id BINARY(16) NOT NULL, CHANGE device_id device_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_channels_controls CHANGE control_id control_id BINARY(16) NOT NULL, CHANGE channel_id channel_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_channels_properties CHANGE property_id property_id BINARY(16) NOT NULL, CHANGE channel_id channel_id BINARY(16) NOT NULL, CHANGE parent_id parent_id BINARY(16) DEFAULT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_connectors CHANGE connector_id connector_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_connectors_controls CHANGE control_id control_id BINARY(16) NOT NULL, CHANGE connector_id connector_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_connectors_properties CHANGE property_id property_id BINARY(16) NOT NULL, CHANGE connector_id connector_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_devices CHANGE device_id device_id BINARY(16) NOT NULL, CHANGE connector_id connector_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_devices_children CHANGE child_device child_device BINARY(16) NOT NULL, CHANGE parent_device parent_device BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_devices_controls CHANGE control_id control_id BINARY(16) NOT NULL, CHANGE device_id device_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_devices_properties CHANGE property_id property_id BINARY(16) NOT NULL, CHANGE device_id device_id BINARY(16) NOT NULL, CHANGE parent_id parent_id BINARY(16) DEFAULT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_channels_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL, CHANGE data_source_property data_source_property BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_connectors_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL, CHANGE data_source_property data_source_property BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_devices_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL, CHANGE data_source_property data_source_property BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_homekit_connector_clients CHANGE client_id client_id BINARY(16) NOT NULL, CHANGE connector_id connector_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_security_policies CHANGE policy_id policy_id BINARY(16) NOT NULL, CHANGE parent_id parent_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_security_tokens CHANGE token_id token_id BINARY(16) NOT NULL, CHANGE parent_id parent_id BINARY(16) DEFAULT NULL, CHANGE identity_id identity_id BINARY(16) DEFAULT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_actions CHANGE action_id action_id BINARY(16) NOT NULL, CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_conditions CHANGE condition_id condition_id BINARY(16) NOT NULL, CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_notifications CHANGE notification_id notification_id BINARY(16) NOT NULL, CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL, CHANGE notification_phone notification_phone VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_triggers CHANGE trigger_id trigger_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_triggers_controls CHANGE control_id control_id BINARY(16) NOT NULL, CHANGE trigger_id trigger_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_dashboards CHANGE dashboard_id dashboard_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_groups CHANGE group_id group_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_groups CHANGE group_id group_id BINARY(16) NOT NULL, CHANGE widget_id widget_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_tabs CHANGE tab_id tab_id BINARY(16) NOT NULL, CHANGE dashboard_id dashboard_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_tabs CHANGE tab_id tab_id BINARY(16) NOT NULL, CHANGE widget_id widget_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets CHANGE widget_id widget_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL, CHANGE widget_id widget_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_display CHANGE display_id display_id BINARY(16) NOT NULL, CHANGE widget_id widget_id BINARY(16) NOT NULL, CHANGE params params JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_generic_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Restores the comments, so a rollback to a DBAL 3 deployment still diffs clean.
        $this->addSql('ALTER TABLE fb_accounts_module_accounts CHANGE account_id account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_accounts_module_accounts_details CHANGE detail_id detail_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE account_id account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_accounts_module_emails CHANGE email_id email_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE account_id account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_accounts_module_identities CHANGE identity_id identity_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE account_id account_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_channels CHANGE channel_id channel_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE device_id device_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_channels_controls CHANGE control_id control_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE channel_id channel_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_channels_properties CHANGE property_id property_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE channel_id channel_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_id parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_connectors CHANGE connector_id connector_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_devices_module_connectors_controls CHANGE control_id control_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE connector_id connector_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_connectors_properties CHANGE property_id property_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE connector_id connector_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_devices CHANGE device_id device_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE connector_id connector_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_devices_children CHANGE child_device child_device BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_device parent_device BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_devices_controls CHANGE control_id control_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE device_id device_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_devices_properties CHANGE property_id property_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE device_id device_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_id parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_channels_data_sources CHANGE data_source_property data_source_property BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE data_source_id data_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_connectors_data_sources CHANGE data_source_property data_source_property BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE data_source_id data_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_devices_module_ui_module_bridge_devices_data_sources CHANGE data_source_property data_source_property BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE data_source_id data_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_homekit_connector_clients CHANGE client_id client_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE connector_id connector_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_security_policies CHANGE policy_id policy_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_id parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_security_tokens CHANGE token_id token_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE parent_id parent_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE identity_id identity_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_triggers_module_actions CHANGE action_id action_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_triggers_module_conditions CHANGE condition_id condition_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_triggers_module_notifications CHANGE notification_id notification_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE trigger_id trigger_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE notification_phone notification_phone VARCHAR(150) DEFAULT NULL COMMENT \'(DC2Type:phone)\'');
        $this->addSql('ALTER TABLE fb_triggers_module_triggers CHANGE trigger_id trigger_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_triggers_module_triggers_controls CHANGE control_id control_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE trigger_id trigger_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_dashboards CHANGE dashboard_id dashboard_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_ui_module_groups CHANGE group_id group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_ui_module_tabs CHANGE tab_id tab_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE dashboard_id dashboard_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets CHANGE widget_id widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE widget_id widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_display CHANGE display_id display_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE params params JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE widget_id widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_generic_data_sources CHANGE data_source_id data_source_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_groups CHANGE group_id group_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE widget_id widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE fb_ui_module_widgets_tabs CHANGE tab_id tab_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', CHANGE widget_id widget_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\'');
    }
}
