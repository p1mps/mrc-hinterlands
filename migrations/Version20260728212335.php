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
        $this->addSql('ALTER TABLE contract ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859CD59F015 FOREIGN KEY (linked_contract_id) REFERENCES contract (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract DROP CONSTRAINT FK_E98F2859CD59F015');
        $this->addSql('ALTER TABLE contract DROP COLUMN description');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT fk_e98f2859cd59f015 FOREIGN KEY (linked_contract_id) REFERENCES contract (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
