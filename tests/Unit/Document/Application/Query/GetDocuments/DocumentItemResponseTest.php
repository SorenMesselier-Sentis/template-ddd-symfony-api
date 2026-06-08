<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Query\GetDocuments;

use App\Document\Application\Query\GetDocuments\DocumentItemResponse;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Tests\Unit\UnitTestCase;

final class DocumentItemResponseTest extends UnitTestCase
{
    public function testItDoesNotExposeObjectPath(): void
    {
        $document = Document::create(
            id: DocumentId::random(),
            ownerId: OwnerId::random(),
            bucketName: BucketName::fromString('documents'),
            objectPath: ObjectPath::fromString('owner/secret/file.pdf'),
            originalName: 'file.pdf',
            size: 100,
            mimeType: MimeType::fromString('application/pdf'),
        );
        $document->pullDomainEvents();

        $response = new DocumentItemResponse($document);
        $serialized = (array) json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('objectPath', $serialized);
        $this->assertSame('file.pdf', $response->originalName);
    }
}
