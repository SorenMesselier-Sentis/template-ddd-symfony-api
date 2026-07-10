<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refresh token storage for JWT rotation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(2048) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                revoked BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_refresh_tokens_token ON refresh_tokens (token)');
        $this->addSql('CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
    }
}
