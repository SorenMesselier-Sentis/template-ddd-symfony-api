<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Audit\AuditEntry;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineAuditTrailRepository;
use App\Tests\Integration\IntegrationTestCase;

final class DoctrineAuditTrailRepositoryTest extends IntegrationTestCase
{
    private DoctrineAuditTrailRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em->getConnection()->executeStatement('TRUNCATE TABLE audit_log RESTART IDENTITY CASCADE');
        $this->repository = new DoctrineAuditTrailRepository($this->em->getConnection());
    }

    public function testRecordPersistsAQueryableRow(): void
    {
        $entry = AuditEntry::record(
            actorId: 'admin@example.com',
            action: 'user.roles_updated',
            targetId: 'user-1',
            context: ['roles' => ['ROLE_ADMIN']],
        );

        $this->repository->record($entry);

        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT * FROM audit_log WHERE id = :id',
            ['id' => $entry->id],
        );

        $this->assertNotFalse($row);
        $this->assertSame('admin@example.com', $row['actor_id']);
        $this->assertSame('user.roles_updated', $row['action']);
        $this->assertSame('user-1', $row['target_id']);
        $this->assertSame(['roles' => ['ROLE_ADMIN']], json_decode((string) $row['context'], true));
    }

    public function testRecordAllowsNullActorId(): void
    {
        $entry = AuditEntry::record(
            actorId: null,
            action: 'user.logged_in',
            targetId: 'john@example.com',
        );

        $this->repository->record($entry);

        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT actor_id FROM audit_log WHERE id = :id',
            ['id' => $entry->id],
        );

        $this->assertNotFalse($row);
        $this->assertNull($row['actor_id']);
    }
}
