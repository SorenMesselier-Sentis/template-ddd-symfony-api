<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Aws\S3\Exception\S3Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CompleteMultipartUploadCommandHandler
{
    public function __construct(
        private readonly MultipartUploadSessionRepositoryInterface $sessionRepository,
        private readonly DocumentRepositoryInterface $documentRepository,
        private readonly MultipartDocumentStorageInterface $multipartStorage,
        private readonly EventBusInterface $eventBus,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(CompleteMultipartUploadCommand $command): UploadDocumentResult
    {
        $session = $this->sessionRepository->findActiveByUploadIdAndOwner(
            $command->uploadId,
            $this->ownerContext->ownerId(),
        );

        if (null === $session || [] === $session->parts()) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        try {
            $this->multipartStorage->completeMultipartUpload(
                bucket: $session->bucketName(),
                objectPath: $session->objectPath(),
                uploadId: $session->uploadId(),
                parts: $session->partsForCompletion(),
            );
        } catch (S3Exception) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        $document = Document::create(
            id: $session->documentId(),
            ownerId: $session->ownerId(),
            bucketName: $session->bucketName(),
            objectPath: $session->objectPath(),
            originalName: $session->originalName(),
            size: $session->totalSize(),
            mimeType: $session->mimeType(),
        );

        $this->documentRepository->save($document);
        $this->eventBus->publish(...$document->pullDomainEvents());

        $session->complete();
        $this->sessionRepository->save($session);

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
}
