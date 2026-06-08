<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging\Outbox;

use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentAccessed;
use App\Document\Domain\Event\DocumentDeleted;
use App\Document\Domain\Event\DocumentUploaded;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxEventBus;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class DocumentOutboxEventsTest extends UnitTestCase
{
    /** @var list<array<string, mixed>> */
    private array $insertedRows = [];

    public function testDocumentUploadedRoundTripsThroughOutbox(): void
    {
        $event = new DocumentUploaded(
            aggregateId: 'doc-1',
            ownerId: 'owner-1',
            bucketName: 'documents',
            objectPath: 'owner/doc/file.pdf',
            originalName: 'file.pdf',
            size: 512,
            mimeType: 'application/pdf',
            status: UploadResultStatus::SUCCESS,
        );

        $this->publishToOutbox($event);
        $relayed = $this->relaySingleEvent(DocumentUploaded::class);

        $this->assertInstanceOf(DocumentUploaded::class, $relayed);
        $this->assertSame('document.uploaded', $relayed::eventName());
        $this->assertSame('doc-1', $relayed->aggregateId());
        $this->assertSame(UploadResultStatus::SUCCESS, $relayed->status);
    }

    public function testDocumentDeletedRoundTripsThroughOutbox(): void
    {
        $event = new DocumentDeleted(
            aggregateId: 'doc-2',
            ownerId: 'owner-2',
            bucketName: 'archive',
            objectPath: 'owner/doc/archive.pdf',
            purge: true,
        );

        $this->publishToOutbox($event);
        $relayed = $this->relaySingleEvent(DocumentDeleted::class);

        $this->assertInstanceOf(DocumentDeleted::class, $relayed);
        $this->assertSame('document.deleted', $relayed::eventName());
        $this->assertSame('archive', $relayed->bucketName);
        $this->assertTrue($relayed->purge);
    }

    public function testDocumentAccessedRoundTripsThroughOutbox(): void
    {
        $event = new DocumentAccessed(
            aggregateId: 'doc-3',
            requesterId: 'requester-3',
        );

        $this->publishToOutbox($event);
        $relayed = $this->relaySingleEvent(DocumentAccessed::class);

        $this->assertInstanceOf(DocumentAccessed::class, $relayed);
        $this->assertSame('document.accessed', $relayed::eventName());
        $this->assertSame('requester-3', $relayed->requesterId);
    }

    private function publishToOutbox(DomainEvent $event): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'outbox_messages',
                $this->callback(function (array $row) use ($event): bool {
                    $this->insertedRows[] = $row;

                    return $event::eventName() === $row['event_name']
                        && $event::class === $row['event_class']
                        && $event->aggregateId() === $row['aggregate_id'];
                }),
            );

        (new OutboxEventBus($connection))->publish($event);
    }

    private function relaySingleEvent(string $eventClass): DomainEvent
    {
        $row = $this->insertedRows[0];
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([[
                'id' => (string) $row['id'],
                'event_class' => (string) $row['event_class'],
                'aggregate_id' => (string) $row['aggregate_id'],
                'payload' => (string) $row['payload'],
            ]]);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'outbox_messages',
                $this->arrayHasKey('published_at'),
                ['id' => (string) $row['id']],
            );

        $relayed = null;
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static function (DomainEvent $event) use (&$relayed): Envelope {
                $relayed = $event;

                return new Envelope(new \stdClass());
            });

        $relay = new OutboxRelay(
            $connection,
            $eventBus,
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $relay->relay();

        $this->assertInstanceOf($eventClass, $relayed);

        return $relayed;
    }
}
