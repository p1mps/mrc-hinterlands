<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804164637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pilot ALTER piloting_xp DROP DEFAULT');
        $this->addSql('ALTER TABLE salvaged_mech ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ALTER tonnage SET NOT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD CONSTRAINT FK_F1533AC5979B1AD6 FOREIGN KEY (company_id) REFERENCES mercenary_company (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_F1533AC5979B1AD6 ON salvaged_mech (company_id)');
        $this->addSql('ALTER TABLE unit ADD dropship_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE unit ADD CONSTRAINT FK_DCBB0C539863015 FOREIGN KEY (dropship_id) REFERENCES dropship (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_DCBB0C539863015 ON unit (dropship_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pilot ALTER piloting_xp SET DEFAULT 0');
        $this->addSql('ALTER TABLE salvaged_mech DROP CONSTRAINT FK_F1533AC5979B1AD6');
        $this->addSql('DROP INDEX IDX_F1533AC5979B1AD6');
        $this->addSql('ALTER TABLE salvaged_mech DROP company_id');
        $this->addSql('ALTER TABLE salvaged_mech ALTER tonnage DROP NOT NULL');
        $this->addSql('ALTER TABLE unit DROP CONSTRAINT FK_DCBB0C539863015');
        $this->addSql('DROP INDEX IDX_DCBB0C539863015');
        $this->addSql('ALTER TABLE unit DROP dropship_id');
    }
}
