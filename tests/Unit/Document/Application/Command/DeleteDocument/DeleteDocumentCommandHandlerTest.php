<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Command\DeleteDocument;

use App\Document\Application\Command\DeleteDocument\DeleteDocumentCommand;
use App\Document\Application\Command\DeleteDocument\DeleteDocumentCommandHandler;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Event\DocumentDeleted;
use App\Document\Domain\Exception\DocumentNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Tests\Unit\UnitTestCase;

final class DeleteDocumentCommandHandlerTest extends UnitTestCase
{
    public function testUserSoftDeletesOwnDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->activeDocument($ownerId);
        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);
        $repository->expects($this->once())->method('save')->with($this->callback(
            static fn (Document $saved): bool => DocumentStatus::DELETED === $saved->status(),
        ));

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects($this->never())->method('delete');

        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->once())->method('publish')->with($this->callback(
            static fn (DocumentDeleted $event): bool => false === $event->purge,
        ));

        $result = $this->handler($repository, $storage, $eventBus, $ownerId)->__invoke(
            new DeleteDocumentCommand(documentId: $document->id()->value()),
        );

        $this->assertSame('deleted', $result->status);
        $this->assertFalse($result->purged);
    }

    public function testUserPurgesOwnDocumentFromStorage(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->activeDocument($ownerId);
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);

        $storage = $this->createMock(DocumentStorageInterface::class);
        $storage->expects($this->once())->method('delete');

        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->once())->method('publish')->with($this->callback(
            static fn (DocumentDeleted $event): bool => true === $event->purge,
        ));

        $result = $this->handler($repository, $storage, $eventBus, $ownerId)->__invoke(
            new DeleteDocumentCommand(documentId: $document->id()->value(), purge: true),
        );

        $this->assertTrue($result->purged);
    }

    public function testAdminDeletesAnyActiveDocument(): void
    {
        $document = $this->activeDocument(OwnerId::random());
        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);
        $repository->expects($this->once())->method('save');

        $result = $this->handler($repository, roles: ['ROLE_ADMIN'])->__invoke(
            new DeleteDocumentCommand(documentId: $document->id()->value()),
        );

        $this->assertSame('deleted', $result->status);
    }

    public function testUserCannotDeleteAnotherOwnersDocument(): void
    {
        $document = $this->activeDocument(OwnerId::random());
        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);
        $repository->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);

        $this->handler($repository, ownerId: OwnerId::random())->__invoke(
            new DeleteDocumentCommand(documentId: $document->id()->value()),
        );
    }

    public function testItReturnsNotFoundForMissingDocument(): void
    {
        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);

        $this->handler($repository)->__invoke(
            new DeleteDocumentCommand(documentId: DocumentId::random()->value()),
        );
    }

    public function testItReturnsNotFoundForAlreadyDeletedDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->activeDocument($ownerId);
        $document->delete(false);
        $document->pullDomainEvents();

        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($document);

        $this->expectException(DocumentNotFoundException::class);

        $this->handler($repository, ownerId: $ownerId)->__invoke(
            new DeleteDocumentCommand(documentId: $document->id()->value()),
        );
    }

    private function handler(
        DocumentRepositoryInterface $repository,
        ?DocumentStorageInterface $storage = null,
        ?EventBusInterface $eventBus = null,
        ?OwnerId $ownerId = null,
        array $roles = ['ROLE_USER'],
    ): DeleteDocumentCommandHandler {
        return new DeleteDocumentCommandHandler(
            repository: $repository,
            storage: $storage ?? $this->createStub(DocumentStorageInterface::class),
            eventBus: $eventBus ?? $this->createStub(EventBusInterface::class),
            ownerContext: $this->ownerContext($ownerId ?? OwnerId::random(), $roles),
        );
    }

    private function ownerContext(OwnerId $ownerId, array $roles): OwnerContextInterface
    {
        $context = $this->createStub(OwnerContextInterface::class);
        $context->method('ownerId')->willReturn($ownerId);
        $context->method('roles')->willReturn($roles);

        return $context;
    }

    private function activeDocument(OwnerId $ownerId): Document
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
