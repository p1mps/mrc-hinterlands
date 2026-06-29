<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bv_cost and acquired fields to salvaged_mech';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salvaged_mech ADD bv_cost INT DEFAULT NULL, ADD acquired TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salvaged_mech DROP bv_cost, DROP acquired');
    }
}
