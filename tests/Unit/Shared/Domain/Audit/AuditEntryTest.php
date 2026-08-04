<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Audit;

use App\Shared\Domain\Audit\AuditEntry;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class AuditEntryTest extends UnitTestCase
{
    public function testRecordGeneratesAValidUuidAndCapturesFields(): void
    {
        $before = new \DateTimeImmutable();
        $entry = AuditEntry::record('admin@example.com', 'user.deleted', 'user-1', ['reason' => 'gdpr']);
        $after = new \DateTimeImmutable();

        $this->assertTrue(Uuid::isValid($entry->id));
        $this->assertSame('admin@example.com', $entry->actorId);
        $this->assertSame('user.deleted', $entry->action);
        $this->assertSame('user-1', $entry->targetId);
        $this->assertSame(['reason' => 'gdpr'], $entry->context);
        $this->assertGreaterThanOrEqual($before, $entry->occurredAt);
        $this->assertLessThanOrEqual($after, $entry->occurredAt);
    }

    public function testContextDefaultsToEmptyArray(): void
    {
        $entry = AuditEntry::record(null, 'user.logged_in', 'john@example.com');

        $this->assertNull($entry->actorId);
        $this->assertSame([], $entry->context);
    }
}
