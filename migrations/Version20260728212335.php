<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260728212335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract DROP CONSTRAINT fk_e98f2859cd59f015');
        $this->addSql('ALTER TABLE contract ALTER COLUMN description TYPE TEXT');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859CD59F015 FOREIGN KEY (linked_contract_id) REFERENCES contract (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE contract_log_entry DROP CONSTRAINT fk_70799797e1076051');
        $this->addSql('DROP INDEX uniq_70799797e1076051');
        $this->addSql('ALTER TABLE contract_log_entry DROP salvaged_mech_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract DROP CONSTRAINT FK_E98F2859CD59F015');
        $this->addSql('ALTER TABLE contract ALTER COLUMN description TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT fk_e98f2859cd59f015 FOREIGN KEY (linked_contract_id) REFERENCES contract (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contract_log_entry ADD salvaged_mech_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_log_entry ADD CONSTRAINT fk_70799797e1076051 FOREIGN KEY (salvaged_mech_id) REFERENCES salvaged_mech (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_70799797e1076051 ON contract_log_entry (salvaged_mech_id)');
    }
}
