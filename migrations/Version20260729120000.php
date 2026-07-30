<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Platform\Migrations\AlphaComparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add MRC salvage rules fields to salvaged_mech table and contract relationship';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to salvaged_mech
        $this->addSql('ALTER TABLE salvaged_mech ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD damage_state VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD tech_base VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD is_truly_destroyed BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE salvaged_mech ADD sp_taken INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD salvage_value INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salvaged_mech ADD salvage_rights_percent INT DEFAULT NULL');
        
        // Add foreign key to contract
        $this->addSql('ALTER TABLE salvaged_mech ADD CONSTRAINT FK_BCBCA8949B5B7F0 FOREIGN KEY (contract_id) REFERENCES contract (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BCBCA8949B5B7F0 ON salvaged_mech (contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salvaged_mech DROP FOREIGN KEY FK_BCBCA8949B5B7F0');
        $this->addSql('DROP INDEX IDX_BCBCA8949B5B7F0');
        $this->addSql('ALTER TABLE salvaged_mech DROP contract_id, DROP damage_state, DROP tech_base, DROP is_truly_destroyed, DROP sp_taken, DROP salvage_value, DROP salvage_rights_percent');
    }
}
