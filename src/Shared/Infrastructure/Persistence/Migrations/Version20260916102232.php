<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916102232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consents table and users.deleted_at (GDPR consent management and retention automation).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE consents (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                type VARCHAR(30) NOT NULL,
                version VARCHAR(50) NOT NULL,
                given_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                withdrawn_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_consents_user_id_type ON consents (user_id, type)');

        $this->addSql('ALTER TABLE users ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP deleted_at');
        $this->addSql('DROP TABLE consents');
    }
}
