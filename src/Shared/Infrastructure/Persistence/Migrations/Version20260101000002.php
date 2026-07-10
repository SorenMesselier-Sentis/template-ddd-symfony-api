<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transactional outbox for domain events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_messages (
                id UUID NOT NULL,
                event_name VARCHAR(255) NOT NULL,
                event_class VARCHAR(255) NOT NULL,
                aggregate_id UUID DEFAULT NULL,
                payload JSON NOT NULL,
                occurred_on TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_outbox_unpublished ON outbox_messages (published_at, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_messages');
    }
}
