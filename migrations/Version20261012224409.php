<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012224409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Security: fb_security_api_keys, the API keys of Core\'s Security capability (SHA-256 hash of the secret, account, state, expiry)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fb_security_api_keys (key_id BINARY(16) NOT NULL, account_id BINARY(16) NOT NULL, key_name VARCHAR(100) NOT NULL, key_hash VARCHAR(64) NOT NULL, key_state VARCHAR(255) DEFAULT \'active\' NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX key_account_idx (account_id), UNIQUE INDEX key_hash_unique (key_hash), PRIMARY KEY (key_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE fb_security_api_keys');
    }
}
