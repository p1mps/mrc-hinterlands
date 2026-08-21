<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused salvage_value column from salvaged_mech table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech DROP salvage_value');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech ADD salvage_value INT DEFAULT NULL');
    }
}
