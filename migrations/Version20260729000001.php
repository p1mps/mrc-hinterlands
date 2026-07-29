<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $exists = $this->connection->fetchOne(
            "SELECT 1 FROM contract WHERE id IN (2, 4, 6, 8, 9) LIMIT 1"
        );

        if (!$exists) {
            return;
        }

        $this->addSql('DROP INDEX IF EXISTS uniq_e98f2859cd59f015');
        $this->addSql("UPDATE contract SET linked_contract_id = 2 WHERE id = 4");
        $this->addSql("UPDATE contract SET linked_contract_id = 6 WHERE id = 8");
        $this->addSql("UPDATE contract SET linked_contract_id = 6 WHERE id = 9");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE contract SET linked_contract_id = NULL WHERE id IN (4, 8, 9)');
    }
}
