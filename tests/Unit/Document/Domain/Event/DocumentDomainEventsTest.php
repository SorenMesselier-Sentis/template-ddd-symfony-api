<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Domain\Event;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentAccessed;
use App\Document\Domain\Event\DocumentDeleted;
use App\Document\Domain\Event\DocumentUploaded;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Tests\Unit\UnitTestCase;

final class DocumentDomainEventsTest extends UnitTestCase
{
    public function testDocumentUploadedEventNameAndPayload(): void
    {
        $event = new DocumentUploaded(
            aggregateId: 'doc-1',
            ownerId: 'owner-1',
            bucketName: 'documents',
            objectPath: 'owner/doc/file.pdf',
            originalName: 'file.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            status: UploadResultStatus::FAILED,
        );

        $this->assertSame('document.uploaded', DocumentUploaded::eventName());
        $this->assertSame('doc-1', $event->aggregateId());
        $this->assertSame('owner-1', $event->ownerId);
        $this->assertSame('documents', $event->bucketName);
        $this->assertSame('owner/doc/file.pdf', $event->objectPath);
        $this->assertSame(UploadResultStatus::FAILED, $event->status);
        $this->assertNotSame('', $event->occurredOn());
    }

    public function testDocumentDeletedEventNameAndPayload(): void
    {
        $ownerId = OwnerId::random();
        $document = Document::create(
            id: DocumentId::random(),
            ownerId: $ownerId,
            bucketName: BucketName::fromString('documents'),
            objectPath: ObjectPath::fromString('owner/doc/file.pdf'),
            originalName: 'file.pdf',
            size: 1024,
            mimeType: MimeType::fromString('application/pdf'),
        );
        $document->pullDomainEvents();
        $document->delete(true);
        $events = $document->pullDomainEvents();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(DocumentDeleted::class, $event);
        $this->assertSame('document.deleted', DocumentDeleted::eventName());
        $this->assertSame($ownerId->value(), $event->ownerId);
        $this->assertSame('documents', $event->bucketName);
        $this->assertSame('owner/doc/file.pdf', $event->objectPath);
        $this->assertTrue($event->purge);
        $this->assertNotSame('', $event->occurredOn());
    }

    public function testDocumentAccessedEventNameAndPayload(): void
    {
        $event = new DocumentAccessed(
            aggregateId: 'doc-1',
            requesterId: 'requester-1',
        );

        $this->assertSame('document.accessed', DocumentAccessed::eventName());
        $this->assertSame('doc-1', $event->aggregateId());
        $this->assertSame('requester-1', $event->requesterId);
        $this->assertNotSame('', $event->occurredOn());
    }
}
