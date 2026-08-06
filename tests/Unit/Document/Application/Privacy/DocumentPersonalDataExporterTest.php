<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Privacy;

use App\Document\Application\Privacy\DocumentPersonalDataExporter;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Tests\Unit\UnitTestCase;

final class DocumentPersonalDataExporterTest extends UnitTestCase
{
    public function testKeyIsDocuments(): void
    {
        $exporter = new DocumentPersonalDataExporter($this->createStub(DocumentRepositoryInterface::class));

        $this->assertSame('documents', $exporter->key());
    }

    public function testExportReturnsOwnedDocuments(): void
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

        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByOwnerId')
            ->with($this->callback(fn (OwnerId $id) => $id->equals($ownerId)))
            ->willReturn([$document]);

        $exporter = new DocumentPersonalDataExporter($repository);
        $data = $exporter->export($ownerId->value());

        $this->assertCount(1, $data);
        $this->assertSame($document->id()->value(), $data[0]['id']);
        $this->assertSame('file.pdf', $data[0]['original_name']);
        $this->assertSame('application/pdf', $data[0]['mime_type']);
    }
}
