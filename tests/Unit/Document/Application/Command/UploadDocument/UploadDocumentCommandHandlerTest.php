<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Command\UploadDocument;

use App\Document\Application\Command\UploadDocument\RecordDocumentUploadFailureCommand;
use App\Document\Application\Command\UploadDocument\RecordDocumentUploadFailureCommandHandler;
use App\Document\Application\Command\UploadDocument\UploadDocumentCommand;
use App\Document\Application\Command\UploadDocument\UploadDocumentCommandHandler;
use App\Document\Application\Service\BucketMimeTypeValidator;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentUploaded;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class UploadDocumentCommandHandlerTest extends UnitTestCase
{
    private DocumentRepositoryInterface $repository;
    private DocumentStorageInterface $storage;
    private BucketExistenceCheckerInterface $bucketChecker;
    private EventBusInterface $eventBus;
    private OwnerContextInterface $ownerContext;
    private AuditBusFake $auditBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createStub(DocumentRepositoryInterface::class);
        $this->storage = $this->createStub(DocumentStorageInterface::class);
        $this->bucketChecker = $this->createStub(BucketExistenceCheckerInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->auditBus = new AuditBusFake();

        $this->ownerContext->method('ownerId')->willReturn(OwnerId::random());
    }

    public function testItUploadsValidDocument(): void
    {
        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $eventBus = $this->createMock(EventBusInterface::class);
        $storage = $this->createMock(DocumentStorageInterface::class);
        $this->bucketChecker->method('exists')->willReturn(true);
        $storage->expects($this->once())->method('upload');
        $repository->expects($this->once())->method('save')->with($this->isInstanceOf(Document::class));
        $eventBus->expects($this->once())->method('publish')->with($this->isInstanceOf(DocumentUploaded::class));

        $result = $this->handler($repository, $eventBus, $storage)->__invoke($this->validCommand());

        $this->assertSame('sample.pdf', $result->originalName);
        $this->assertSame('active', $result->status);
        $this->assertCount(0, $this->auditBus->dispatched);
    }

    public function testItRejectsOversizedFile(): void
    {
        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects($this->never())->method('upload');

        $this->expectException(FileTooLargeException::class);

        try {
            $this->handler(storage: $storage)->__invoke(new UploadDocumentCommand(
                id: DocumentId::random()->value(),
                bucket: 'documents',
                originalName: 'large.pdf',
                content: 'x',
                size: 104857601,
                mimeType: 'application/pdf',
            ));
        } finally {
            $this->assertCount(1, $this->auditBus->dispatched);
            $this->assertInstanceOf(RecordDocumentUploadFailureCommand::class, $this->auditBus->dispatched[0]);
        }
    }

    public function testItRejectsMissingBucket(): void
    {
        $storage = $this->createMock(DocumentStorageInterface::class);
        $this->bucketChecker->method('exists')->willReturn(false);
        $storage->expects($this->never())->method('upload');

        $this->expectException(BucketNotFoundException::class);

        try {
            $this->handler(storage: $storage)->__invoke($this->validCommand());
        } finally {
            $this->assertCount(1, $this->auditBus->dispatched);
        }
    }

    public function testItRejectsInvalidMimeType(): void
    {
        $storage = $this->createMock(DocumentStorageInterface::class);
        $this->bucketChecker->method('exists')->willReturn(true);
        $storage->expects($this->never())->method('upload');

        $this->expectException(InvalidMimeTypeException::class);

        try {
            $this->handler(storage: $storage)->__invoke(new UploadDocumentCommand(
                id: DocumentId::random()->value(),
                bucket: 'documents',
                originalName: 'sample.txt',
                content: 'hello',
                size: 5,
                mimeType: 'text/plain',
            ));
        } finally {
            $this->assertCount(1, $this->auditBus->dispatched);
        }
    }

    public function testFailureRecorderPublishesFailedEvent(): void
    {
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->once())->method('publish')->with($this->callback(
            static fn (DocumentUploaded $event): bool => UploadResultStatus::FAILED === $event->status,
        ));

        $handler = new RecordDocumentUploadFailureCommandHandler($eventBus);
        $handler(new RecordDocumentUploadFailureCommand(
            documentId: DocumentId::random()->value(),
            ownerId: OwnerId::random()->value(),
            bucket: 'documents',
            objectPath: 'owner/doc/file.pdf',
            originalName: 'file.pdf',
            size: 10,
            mimeType: 'application/pdf',
        ));
    }

    private function handler(
        ?DocumentRepositoryInterface $repository = null,
        ?EventBusInterface $eventBus = null,
        ?DocumentStorageInterface $storage = null,
    ): UploadDocumentCommandHandler {
        return new UploadDocumentCommandHandler(
            repository: $repository ?? $this->repository,
            storage: $storage ?? $this->storage,
            bucketChecker: $this->bucketChecker,
            mimeTypeValidator: new BucketMimeTypeValidator([
                'documents' => ['allowed_mime_types' => ['application/pdf']],
            ]),
            eventBus: $eventBus ?? $this->eventBus,
            ownerContext: $this->ownerContext,
            auditBus: $this->auditBus,
            maxSinglePartBytes: 104857600,
        );
    }

    private function validCommand(): UploadDocumentCommand
    {
        return new UploadDocumentCommand(
            id: DocumentId::random()->value(),
            bucket: 'documents',
            originalName: 'sample.pdf',
            content: '%PDF-1.4',
            size: 8,
            mimeType: 'application/pdf',
        );
    }
}

final class AuditBusFake implements MessageBusInterface
{
    /** @var list<object> */
    public array $dispatched = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;

        return new Envelope($message);
    }
}
