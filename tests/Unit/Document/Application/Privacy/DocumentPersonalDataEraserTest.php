<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Privacy;

use App\Document\Application\Privacy\DocumentPersonalDataEraser;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class DocumentPersonalDataEraserTest extends UnitTestCase
{
    public function testKeyIsDocuments(): void
    {
        $eraser = new DocumentPersonalDataEraser(
            $this->createStub(DocumentRepositoryInterface::class),
            $this->createStub(DocumentStorageInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame('documents', $eraser->key());
    }

    public function testErasePurgesStorageAndHardDeletesEveryOwnedDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId);

        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByOwnerIdIncludingDeleted')
            ->with($this->callback(fn (OwnerId $id) => $id->equals($ownerId)))
            ->willReturn([$document]);
        $repository->expects($this->once())->method('delete')->with($document);

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects($this->once())
            ->method('delete')
            ->with($document->bucketName(), $document->objectPath());

        $eraser = new DocumentPersonalDataEraser($repository, $storage, $this->createStub(LoggerInterface::class));
        $eraser->erase($ownerId->value());
    }

    public function testEraseStillHardDeletesTheRowWhenStorageDeletionFails(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId);

        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->method('findByOwnerIdIncludingDeleted')->willReturn([$document]);
        $repository->expects($this->once())->method('delete')->with($document);

        $storage = $this->createStub(DocumentStorageInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('object not found'));

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $eraser = new DocumentPersonalDataEraser($repository, $storage, $logger);
        $eraser->erase($ownerId->value());
    }

    private function document(OwnerId $ownerId): Document
    {
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

        return $document;
    }
}
