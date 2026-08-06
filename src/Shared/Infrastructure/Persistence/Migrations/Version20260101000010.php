<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feature_flags table and seed the cursor_pagination flag.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE feature_flags (
                flag_key VARCHAR(100) NOT NULL,
                enabled BOOLEAN NOT NULL DEFAULT false,
                description VARCHAR(255) DEFAULT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (flag_key)
            )
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO feature_flags (flag_key, enabled, description, updated_at)
            VALUES ('cursor_pagination', true, 'Keyset (cursor) pagination on GET /users and GET /documents.', now())
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feature_flags');
    }
}
