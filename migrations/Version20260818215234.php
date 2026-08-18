<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818215234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salvaged_mech ADD repair_cost INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER roles DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salvaged_mech DROP repair_cost');
        $this->addSql('ALTER TABLE salvaged_mech ALTER created_at SET DEFAULT \'1970-01-01 00:00:00\'');
        $this->addSql('ALTER TABLE "user" ALTER roles SET DEFAULT \'["ROLE_USER"]\'');
    }
}
