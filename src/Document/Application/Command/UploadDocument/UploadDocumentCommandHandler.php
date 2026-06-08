<?php

declare(strict_types=1);

namespace App\Document\Application\Command\UploadDocument;

use App\Document\Application\Service\BucketMimeTypeValidator;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class UploadDocumentCommandHandler
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly DocumentStorageInterface $storage,
        private readonly BucketExistenceCheckerInterface $bucketChecker,
        private readonly BucketMimeTypeValidator $mimeTypeValidator,
        private readonly EventBusInterface $eventBus,
        private readonly OwnerContextInterface $ownerContext,
        private readonly MessageBusInterface $auditBus,
        private readonly int $maxSinglePartBytes,
    ) {
    }

    public function __invoke(UploadDocumentCommand $command): UploadDocumentResult
    {
        $documentId = DocumentId::fromString($command->id);
        $ownerId = $this->ownerContext->ownerId();
        $bucket = BucketName::fromString($command->bucket);
        $mimeType = MimeType::fromString($command->mimeType);
        $objectPath = ObjectPath::forDocument($ownerId, $documentId, $command->originalName);

        if ($command->size > $this->maxSinglePartBytes) {
            $this->recordFailure($documentId, $ownerId, $bucket, $objectPath, $command);

            throw FileTooLargeException::exceedsMaximum($this->maxSinglePartBytes);
        }

        if (!$this->bucketChecker->exists($bucket)) {
            $this->recordFailure($documentId, $ownerId, $bucket, $objectPath, $command);

            throw BucketNotFoundException::withName($bucket->value());
        }

        try {
            $this->mimeTypeValidator->validate($bucket, $mimeType);
        } catch (\Throwable $exception) {
            $this->recordFailure($documentId, $ownerId, $bucket, $objectPath, $command);

            throw $exception;
        }

        $this->storage->upload($bucket, $objectPath, $command->content, $mimeType);

        $document = Document::create(
            id: $documentId,
            ownerId: $ownerId,
            bucketName: $bucket,
            objectPath: $objectPath,
            originalName: $command->originalName,
            size: $command->size,
            mimeType: $mimeType,
        );

        $this->repository->save($document);
        $this->eventBus->publish(...$document->pullDomainEvents());

        return new UploadDocumentResult(
            id: $document->id()->value(),
            originalName: $document->originalName(),
            size: $document->size(),
            mimeType: $document->mimeType()->value(),
            bucket: $document->bucketName()->value(),
            objectPath: $document->objectPath()->value(),
            ownerId: $document->ownerId()->value(),
            status: $document->status()->value,
            createdAt: $document->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $document->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function recordFailure(
        DocumentId $documentId,
        OwnerId $ownerId,
        BucketName $bucket,
        ObjectPath $objectPath,
        UploadDocumentCommand $command,
    ): void {
        $this->auditBus->dispatch(new RecordDocumentUploadFailureCommand(
            documentId: $documentId->value(),
            ownerId: $ownerId->value(),
            bucket: $bucket->value(),
            objectPath: $objectPath->value(),
            originalName: $command->originalName,
            size: $command->size,
            mimeType: $command->mimeType,
        ));
    }
}
