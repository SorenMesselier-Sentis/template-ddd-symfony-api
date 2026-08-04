<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_log table for sensitive-action traceability.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_log (
                id UUID NOT NULL,
                actor_id VARCHAR(255) DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                target_id VARCHAR(255) NOT NULL,
                context JSON NOT NULL,
                occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_audit_log_target_id ON audit_log (target_id)');
        $this->addSql('CREATE INDEX idx_audit_log_action ON audit_log (action)');
        $this->addSql('CREATE INDEX idx_audit_log_occurred_at ON audit_log (occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
