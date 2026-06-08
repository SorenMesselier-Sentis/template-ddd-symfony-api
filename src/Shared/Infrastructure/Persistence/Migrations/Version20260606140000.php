<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add multipart upload sessions table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE multipart_upload_sessions (
                upload_id VARCHAR(255) NOT NULL,
                document_id UUID NOT NULL,
                owner_id UUID NOT NULL,
                bucket_name VARCHAR(63) NOT NULL,
                object_path VARCHAR(1024) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(127) NOT NULL,
                total_size INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                parts JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (upload_id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_multipart_upload_sessions_owner_id ON multipart_upload_sessions (owner_id)');
        $this->addSql('CREATE INDEX idx_multipart_upload_sessions_status ON multipart_upload_sessions (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE multipart_upload_sessions');
    }
}
