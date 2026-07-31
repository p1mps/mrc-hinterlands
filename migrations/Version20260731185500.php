<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731185500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split single xp column into gunnery_xp and piloting_xp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pilot RENAME COLUMN xp TO gunnery_xp');
        $this->addSql('ALTER TABLE pilot ADD COLUMN piloting_xp INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pilot RENAME COLUMN gunnery_xp TO xp');
        $this->addSql('ALTER TABLE pilot DROP COLUMN piloting_xp');
    }
}
