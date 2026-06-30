<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630200412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech DROP name');
        $this->addSql('ALTER TABLE salvaged_mech DROP configuration');
        $this->addSql('ALTER TABLE salvaged_mech DROP salvaged_at');
        $this->addSql('ALTER TABLE salvaged_mech DROP source_log_entry_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
