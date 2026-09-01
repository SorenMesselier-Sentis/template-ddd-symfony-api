<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901124906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_clients and issued_access_tokens tables (ApiClient bounded context — OAuth2 client_credentials)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_clients (name VARCHAR(200) NOT NULL, secret_hash VARCHAR(255) NOT NULL, scopes JSON NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE issued_access_tokens (scopes JSON NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(100) NOT NULL, api_client_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_issued_access_tokens_api_client_id ON issued_access_tokens (api_client_id)');
        $this->addSql('ALTER TABLE issued_access_tokens ADD CONSTRAINT FK_issued_access_tokens_api_client_id FOREIGN KEY (api_client_id) REFERENCES api_clients (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE issued_access_tokens DROP CONSTRAINT FK_issued_access_tokens_api_client_id');
        $this->addSql('DROP TABLE issued_access_tokens');
        $this->addSql('DROP TABLE api_clients');
    }
}
