<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260809144115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dropship ALTER mekbay_capacity DROP DEFAULT');
        $this->addSql("ALTER TABLE salvaged_mech ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT '1970-01-01 00:00:00'");
        $this->addSql("UPDATE salvaged_mech SET created_at = '1970-01-01 00:00:00' WHERE created_at IS NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dropship ALTER mekbay_capacity SET DEFAULT 0');
        $this->addSql('ALTER TABLE salvaged_mech DROP created_at');
    }
}
