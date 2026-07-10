<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE email_verification_tokens (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(2048) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                revoked BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_email_verification_tokens_token ON email_verification_tokens (token)');
        $this->addSql('CREATE INDEX idx_email_verification_tokens_user_id ON email_verification_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE email_verification_tokens');
    }
}
