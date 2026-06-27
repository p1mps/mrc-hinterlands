<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628015000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Connect ContractLogEntry to SupportPointEntry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_log_entry ADD support_point_entry_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_log_entry ADD CONSTRAINT FK_70799797A7B9B420 FOREIGN KEY (support_point_entry_id) REFERENCES support_point_entry (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70799797A7B9B420 ON contract_log_entry (support_point_entry_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_log_entry DROP CONSTRAINT FK_70799797A7B9B420');
        $this->addSql('DROP INDEX UNIQ_70799797A7B9B420');
        $this->addSql('ALTER TABLE contract_log_entry DROP support_point_entry_id');
    }
}
