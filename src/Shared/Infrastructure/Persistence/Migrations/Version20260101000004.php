<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_tokens (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(2048) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                revoked BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_password_reset_tokens_token ON password_reset_tokens (token)');
        $this->addSql('CREATE INDEX idx_password_reset_tokens_user_id ON password_reset_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_reset_tokens');
    }
}
