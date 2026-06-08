<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Command\MultipartUpload;

use App\Document\Application\Command\MultipartUpload\CompleteMultipartUploadCommand;
use App\Document\Application\Command\MultipartUpload\CompleteMultipartUploadCommandHandler;
use App\Document\Application\Command\MultipartUpload\InitiateMultipartUploadCommand;
use App\Document\Application\Command\MultipartUpload\InitiateMultipartUploadCommandHandler;
use App\Document\Application\Command\MultipartUpload\UploadMultipartPartCommand;
use App\Document\Application\Command\MultipartUpload\UploadMultipartPartCommandHandler;
use App\Document\Application\Service\BucketMimeTypeValidator;
use App\Document\Application\Service\MultipartPartSizeValidator;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Entity\MultipartUploadSession;
use App\Document\Domain\Event\DocumentUploaded;
use App\Document\Domain\Exception\InvalidMultipartFileSizeException;
use App\Document\Domain\Exception\InvalidPartSizeException;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Domain\ValueObject\PartNumber;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;

final class MultipartUploadHandlersTest extends UnitTestCase
{
    public function testInitiateRejectsFileSizeOutOfRange(): void
    {
        $this->expectException(InvalidMultipartFileSizeException::class);

        $this->initiateHandler()->__invoke(new InitiateMultipartUploadCommand(
            documentId: DocumentId::random()->value(),
            bucket: 'documents',
            originalName: 'file.pdf',
            totalSize: 1024,
            mimeType: 'application/pdf',
        ));
    }

    public function testInitiateCreatesSession(): void
    {
        $storage = $this->createStub(MultipartDocumentStorageInterface::class);
        $storage->method('initiateMultipartUpload')->willReturn('upload-123');
        $sessions = $this->createMock(MultipartUploadSessionRepositoryInterface::class);
        $sessions->expects($this->once())->method('save');

        $result = $this->initiateHandler($storage, $sessions)->__invoke(new InitiateMultipartUploadCommand(
            documentId: DocumentId::random()->value(),
            bucket: 'documents',
            originalName: 'file.pdf',
            totalSize: 104857600,
            mimeType: 'application/pdf',
        ));

        $this->assertSame('upload-123', $result->uploadId);
    }

    public function testUploadPartRejectsInvalidSize(): void
    {
        $session = $this->activeSession();
        $sessions = $this->createStub(MultipartUploadSessionRepositoryInterface::class);
        $sessions->method('findActiveByUploadIdAndOwner')->willReturn($session);

        $this->expectException(InvalidPartSizeException::class);

        $this->uploadPartHandler($sessions)->__invoke(new UploadMultipartPartCommand(
            uploadId: 'upload-123',
            partNumber: 1,
            content: 'x',
            size: 0,
            isLastPart: false,
        ));
    }

    public function testUploadPartStoresEtag(): void
    {
        $session = $this->activeSession();
        $sessions = $this->createMock(MultipartUploadSessionRepositoryInterface::class);
        $sessions->method('findActiveByUploadIdAndOwner')->willReturn($session);
        $sessions->expects($this->once())->method('save');

        $storage = $this->createMock(MultipartDocumentStorageInterface::class);
        $storage->expects($this->once())->method('uploadPart')->willReturn('"etag-1"');

        $result = $this->uploadPartHandler($sessions, $storage)->__invoke(new UploadMultipartPartCommand(
            uploadId: 'upload-123',
            partNumber: 1,
            content: str_repeat('a', 5242880),
            size: 5242880,
            isLastPart: false,
        ));

        $this->assertSame('"etag-1"', $result->etag);
    }

    public function testCompletePersistsDocumentAndPublishesEvent(): void
    {
        $session = $this->activeSession();
        $session->registerPart(PartNumber::fromInt(1), '"etag-1"', 5242880);

        $sessions = $this->createMock(MultipartUploadSessionRepositoryInterface::class);
        $sessions->method('findActiveByUploadIdAndOwner')->willReturn($session);
        $sessions->expects($this->once())->method('save');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects($this->once())->method('save')->with($this->isInstanceOf(Document::class));

        $events = $this->createMock(EventBusInterface::class);
        $events->expects($this->once())->method('publish')->with($this->isInstanceOf(DocumentUploaded::class));

        $storage = $this->createMock(MultipartDocumentStorageInterface::class);
        $storage->expects($this->once())->method('completeMultipartUpload');

        $result = $this->completeHandler($sessions, $documents, $storage, $events)->__invoke(
            new CompleteMultipartUploadCommand(uploadId: 'upload-123'),
        );

        $this->assertSame('active', $result->status);
    }

    public function testCompleteThrowsWhenSessionMissing(): void
    {
        $sessions = $this->createStub(MultipartUploadSessionRepositoryInterface::class);
        $sessions->method('findActiveByUploadIdAndOwner')->willReturn(null);

        $this->expectException(UploadSessionNotFoundException::class);

        $this->completeHandler($sessions)->__invoke(new CompleteMultipartUploadCommand(uploadId: 'missing'));
    }

    private function initiateHandler(
        ?MultipartDocumentStorageInterface $storage = null,
        ?MultipartUploadSessionRepositoryInterface $sessions = null,
    ): InitiateMultipartUploadCommandHandler {
        $bucketChecker = $this->createStub(BucketExistenceCheckerInterface::class);
        $bucketChecker->method('exists')->willReturn(true);

        return new InitiateMultipartUploadCommandHandler(
            sessionRepository: $sessions ?? $this->createStub(MultipartUploadSessionRepositoryInterface::class),
            multipartStorage: $storage ?? $this->createStub(MultipartDocumentStorageInterface::class),
            bucketChecker: $bucketChecker,
            mimeTypeValidator: new BucketMimeTypeValidator([
                'documents' => ['allowed_mime_types' => ['application/pdf']],
            ]),
            ownerContext: $this->ownerContext(),
            minMultipartBytes: 104857600,
            maxMultipartBytes: 5368709120,
        );
    }

    private function uploadPartHandler(
        ?MultipartUploadSessionRepositoryInterface $sessions = null,
        ?MultipartDocumentStorageInterface $storage = null,
    ): UploadMultipartPartCommandHandler {
        return new UploadMultipartPartCommandHandler(
            sessionRepository: $sessions ?? $this->createStub(MultipartUploadSessionRepositoryInterface::class),
            multipartStorage: $storage ?? $this->createStub(MultipartDocumentStorageInterface::class),
            partSizeValidator: new MultipartPartSizeValidator(5242880),
            ownerContext: $this->ownerContext(),
        );
    }

    private function completeHandler(
        ?MultipartUploadSessionRepositoryInterface $sessions = null,
        ?DocumentRepositoryInterface $documents = null,
        ?MultipartDocumentStorageInterface $storage = null,
        ?EventBusInterface $events = null,
    ): CompleteMultipartUploadCommandHandler {
        return new CompleteMultipartUploadCommandHandler(
            sessionRepository: $sessions ?? $this->createStub(MultipartUploadSessionRepositoryInterface::class),
            documentRepository: $documents ?? $this->createStub(DocumentRepositoryInterface::class),
            multipartStorage: $storage ?? $this->createStub(MultipartDocumentStorageInterface::class),
            eventBus: $events ?? $this->createStub(EventBusInterface::class),
            ownerContext: $this->ownerContext(),
        );
    }

    private function activeSession(): MultipartUploadSession
    {
        $ownerId = OwnerId::random();
        $documentId = DocumentId::random();

        return MultipartUploadSession::initiate(
            uploadId: 'upload-123',
            documentId: $documentId,
            ownerId: $ownerId,
            bucketName: BucketName::fromString('documents'),
            objectPath: ObjectPath::forDocument($ownerId, $documentId, 'file.pdf'),
            originalName: 'file.pdf',
            mimeType: MimeType::fromString('application/pdf'),
            totalSize: 104857600,
        );
    }

    private function ownerContext(): OwnerContextInterface
    {
        $context = $this->createStub(OwnerContextInterface::class);
        $context->method('ownerId')->willReturn(OwnerId::random());

        return $context;
    }
}
