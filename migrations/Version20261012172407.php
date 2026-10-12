<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012172407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Automators: the 14 nullable single-table-inheritance columns of the DateTime and DevicesModule automators';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fb_triggers_module_actions ADD action_device BINARY(16) DEFAULT NULL, ADD action_value VARCHAR(100) DEFAULT NULL, ADD action_channel BINARY(16) DEFAULT NULL, ADD action_channel_property BINARY(16) DEFAULT NULL, ADD action_device_property BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE fb_triggers_module_conditions ADD condition_date DATETIME DEFAULT NULL, ADD condition_time TIME DEFAULT NULL, ADD condition_days LONGTEXT DEFAULT NULL, ADD condition_device BINARY(16) DEFAULT NULL, ADD condition_operator VARCHAR(15) DEFAULT NULL, ADD condition_operand VARCHAR(20) DEFAULT NULL, ADD condition_channel BINARY(16) DEFAULT NULL, ADD condition_channel_property BINARY(16) DEFAULT NULL, ADD condition_device_property BINARY(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fb_triggers_module_actions DROP action_device, DROP action_value, DROP action_channel, DROP action_channel_property, DROP action_device_property');
        $this->addSql('ALTER TABLE fb_triggers_module_conditions DROP condition_date, DROP condition_time, DROP condition_days, DROP condition_device, DROP condition_operator, DROP condition_operand, DROP condition_channel, DROP condition_channel_property, DROP condition_device_property');
    }
}
