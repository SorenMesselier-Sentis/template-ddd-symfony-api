<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Audit\AuditEntry;
use App\Shared\Domain\Audit\AuditTrailInterface;
use Doctrine\DBAL\Connection;

final class DoctrineAuditTrailRepository implements AuditTrailInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function record(AuditEntry $entry): void
    {
        $this->connection->insert('audit_log', [
            'id' => $entry->id,
            'actor_id' => $entry->actorId,
            'action' => $entry->action,
            'target_id' => $entry->targetId,
            'context' => json_encode($entry->context, JSON_THROW_ON_ERROR),
            'occurred_at' => $entry->occurredAt->format('Y-m-d H:i:s'),
        ]);
    }
}
