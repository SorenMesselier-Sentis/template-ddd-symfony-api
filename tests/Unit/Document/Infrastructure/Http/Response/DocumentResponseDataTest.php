<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Infrastructure\Http\Response;

use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Infrastructure\Http\Response\DocumentResponseData;
use App\Tests\Unit\UnitTestCase;

final class DocumentResponseDataTest extends UnitTestCase
{
    public function testItExcludesObjectPathFromApiPayload(): void
    {
        $payload = DocumentResponseData::fromUploadResult(new UploadDocumentResult(
            id: '00000000-0000-0000-0000-000000000001',
            originalName: 'file.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            bucket: 'documents',
            objectPath: 'owner/secret/path/file.pdf',
            ownerId: '00000000-0000-0000-0000-000000000002',
            status: 'active',
            createdAt: '2026-06-08T10:00:00+00:00',
            updatedAt: '2026-06-08T10:00:00+00:00',
        ));

        $this->assertArrayNotHasKey('objectPath', $payload);
        $this->assertSame('file.pdf', $payload['originalName']);
    }

    public function testFromDocumentExcludesObjectPath(): void
    {
        $ownerId = OwnerId::random();
        $document = Document::create(
            id: DocumentId::random(),
            ownerId: $ownerId,
            bucketName: BucketName::fromString('documents'),
            objectPath: ObjectPath::fromString('owner/secret/path/file.pdf'),
            originalName: 'file.pdf',
            size: 1024,
            mimeType: MimeType::fromString('application/pdf'),
        );
        $document->pullDomainEvents();

        $payload = DocumentResponseData::fromDocument($document);

        $this->assertArrayNotHasKey('objectPath', $payload);
        $this->assertSame('documents', $payload['bucket']);
    }
}
