<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806085522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove is_active column from unit table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unit DROP is_active');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unit ADD is_active BOOLEAN NOT NULL');
    }
}
