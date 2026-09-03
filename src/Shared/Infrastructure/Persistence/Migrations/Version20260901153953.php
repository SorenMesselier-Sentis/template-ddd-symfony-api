<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901153953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add webhook_subscriptions table (Webhook bounded context — outbound webhooks)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webhook_subscriptions (name VARCHAR(200) NOT NULL, url VARCHAR(2048) NOT NULL, secret VARCHAR(255) NOT NULL, event_names TEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webhook_subscriptions');
    }
}
