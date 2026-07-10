<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add documents table for document metadata storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE documents (
                id UUID NOT NULL,
                owner_id UUID NOT NULL,
                bucket_name VARCHAR(63) NOT NULL,
                object_path VARCHAR(1024) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                size INT NOT NULL,
                mime_type VARCHAR(127) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_documents_owner_id ON documents (owner_id)');
        $this->addSql('CREATE INDEX idx_documents_bucket_name ON documents (bucket_name)');
        $this->addSql('CREATE INDEX idx_documents_status ON documents (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE documents');
    }
}
