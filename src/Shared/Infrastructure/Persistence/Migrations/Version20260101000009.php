<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for keyset (cursor) pagination on users and documents.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_users_created_at_id ON users (created_at, id)');
        $this->addSql('CREATE INDEX idx_documents_owner_id_created_at_id ON documents (owner_id, created_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_users_created_at_id');
        $this->addSql('DROP INDEX idx_documents_owner_id_created_at_id');
    }
}
