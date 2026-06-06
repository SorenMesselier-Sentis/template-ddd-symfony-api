<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Query\GetDocumentPresignedUrl;

use App\Document\Application\Query\GetDocumentPresignedUrl\GetDocumentPresignedUrlQuery;
use App\Document\Application\Query\GetDocumentPresignedUrl\GetDocumentPresignedUrlQueryHandler;
use App\Document\Application\Service\PresignedUrlTtlValidator;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Event\DocumentAccessed;
use App\Document\Domain\Exception\DocumentNotFoundException;
use App\Document\Domain\Exception\InvalidPresignedUrlTtlException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\DocumentPresignedUrlGeneratorInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Domain\ValueObject\PresignedUrl;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Tests\Unit\UnitTestCase;

final class GetDocumentPresignedUrlQueryHandlerTest extends UnitTestCase
{
    public function testUserCanAccessOwnActiveDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId);
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findById')->willReturn($document);

        $generator = $this->createStub(DocumentPresignedUrlGeneratorInterface::class);
        $generator->method('generatePresignedDownloadUrl')->willReturn(
            new PresignedUrl('https://minio.local/file', 3600, new \DateTimeImmutable('+1 hour')),
        );

        $events = $this->createMock(EventBusInterface::class);
        $events->expects($this->once())->method('publish')->with($this->isInstanceOf(DocumentAccessed::class));

        $result = $this->handler($repository, $generator, $events, $ownerId, ['ROLE_USER'])->__invoke(
            new GetDocumentPresignedUrlQuery(documentId: $document->id()->value()),
        );

        $this->assertSame('https://minio.local/file', $result->presignedUrl);
        $this->assertSame(3600, $result->expiresIn);
    }

    public function testUserCannotAccessAnotherOwnersDocument(): void
    {
        $document = $this->document(OwnerId::random());
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findById')->willReturn($document);

        $this->expectException(ForbiddenException::class);

        $this->handler($repository, ownerId: OwnerId::random(), roles: ['ROLE_USER'])->__invoke(
            new GetDocumentPresignedUrlQuery(documentId: $document->id()->value()),
        );
    }

    public function testUserGetsNotFoundForMissingDocument(): void
    {
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);

        $this->handler($repository)->__invoke(
            new GetDocumentPresignedUrlQuery(documentId: DocumentId::random()->value()),
        );
    }

    public function testAdminCanAccessDeletedDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId, DocumentStatus::DELETED);
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);

        $generator = $this->createStub(DocumentPresignedUrlGeneratorInterface::class);
        $generator->method('generatePresignedDownloadUrl')->willReturn(
            new PresignedUrl('https://minio.local/deleted', 3600, new \DateTimeImmutable('+1 hour')),
        );

        $result = $this->handler($repository, $generator, roles: ['ROLE_ADMIN'])->__invoke(
            new GetDocumentPresignedUrlQuery(documentId: $document->id()->value()),
        );

        $this->assertSame('https://minio.local/deleted', $result->presignedUrl);
    }

    public function testItRejectsInvalidTtl(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId);
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findById')->willReturn($document);

        $this->expectException(InvalidPresignedUrlTtlException::class);

        $this->handler($repository, ownerId: $ownerId)->__invoke(
            new GetDocumentPresignedUrlQuery(documentId: $document->id()->value(), ttlSeconds: 30),
        );
    }

    private function handler(
        DocumentRepositoryInterface $repository,
        ?DocumentPresignedUrlGeneratorInterface $generator = null,
        ?EventBusInterface $events = null,
        ?OwnerId $ownerId = null,
        array $roles = ['ROLE_USER'],
    ): GetDocumentPresignedUrlQueryHandler {
        return new GetDocumentPresignedUrlQueryHandler(
            repository: $repository,
            presignedUrlGenerator: $generator ?? $this->createStub(DocumentPresignedUrlGeneratorInterface::class),
            ttlValidator: new PresignedUrlTtlValidator(60, 604800),
            eventBus: $events ?? $this->createStub(EventBusInterface::class),
            ownerContext: $this->ownerContext($ownerId ?? OwnerId::random(), $roles),
            defaultTtlSeconds: 3600,
        );
    }

    private function ownerContext(OwnerId $ownerId, array $roles): OwnerContextInterface
    {
        $context = $this->createStub(OwnerContextInterface::class);
        $context->method('ownerId')->willReturn($ownerId);
        $context->method('roles')->willReturn($roles);

        return $context;
    }

    private function document(OwnerId $ownerId, DocumentStatus $status = DocumentStatus::ACTIVE): Document
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

        if (DocumentStatus::DELETED === $status) {
            $reflection = new \ReflectionClass($document);
            $property = $reflection->getProperty('status');
            $property->setValue($document, DocumentStatus::DELETED);
        }

        $document->pullDomainEvents();

        return $document;
    }
}
