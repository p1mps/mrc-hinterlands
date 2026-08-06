<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove acquired column from salvaged_mech table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech DROP acquired');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech ADD acquired BOOLEAN DEFAULT false NOT NULL');
    }
}
