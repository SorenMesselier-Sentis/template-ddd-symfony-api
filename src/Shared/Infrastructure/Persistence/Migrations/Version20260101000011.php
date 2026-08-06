<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add projects and tasks tables (Project bounded context).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (
                id UUID NOT NULL,
                owner_id UUID NOT NULL,
                name VARCHAR(100) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_projects_owner_id ON projects (owner_id)');
        $this->addSql('CREATE INDEX idx_projects_status ON projects (status)');

        $this->addSql(<<<'SQL'
            CREATE TABLE tasks (
                id UUID NOT NULL,
                project_id UUID NOT NULL,
                title VARCHAR(200) NOT NULL,
                assignee_id UUID DEFAULT NULL,
                attachment_id UUID DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT fk_tasks_project_id FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX idx_tasks_project_id ON tasks (project_id)');
        $this->addSql('CREATE INDEX idx_tasks_status ON tasks (status)');
        $this->addSql('CREATE INDEX idx_tasks_assignee_id ON tasks (assignee_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE projects');
    }
}
