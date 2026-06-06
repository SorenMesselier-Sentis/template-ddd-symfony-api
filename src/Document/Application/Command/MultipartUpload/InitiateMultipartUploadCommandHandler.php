<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Service\BucketMimeTypeValidator;
use App\Document\Domain\Entity\MultipartUploadSession;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\InvalidMultipartFileSizeException;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class InitiateMultipartUploadCommandHandler
{
    public function __construct(
        private readonly MultipartUploadSessionRepositoryInterface $sessionRepository,
        private readonly MultipartDocumentStorageInterface $multipartStorage,
        private readonly BucketExistenceCheckerInterface $bucketChecker,
        private readonly BucketMimeTypeValidator $mimeTypeValidator,
        private readonly OwnerContextInterface $ownerContext,
        private readonly int $minMultipartBytes,
        private readonly int $maxMultipartBytes,
    ) {
    }

    public function __invoke(InitiateMultipartUploadCommand $command): InitiateMultipartUploadResult
    {
        if ($command->totalSize < $this->minMultipartBytes || $command->totalSize > $this->maxMultipartBytes) {
            throw InvalidMultipartFileSizeException::outOfRange($this->minMultipartBytes, $this->maxMultipartBytes);
        }

        $ownerId = $this->ownerContext->ownerId();
        $documentId = DocumentId::fromString($command->documentId);
        $bucket = BucketName::fromString($command->bucket);
        $mimeType = MimeType::fromString($command->mimeType);
        $objectPath = ObjectPath::forDocument($ownerId, $documentId, $command->originalName);

        if (!$this->bucketChecker->exists($bucket)) {
            throw BucketNotFoundException::withName($bucket->value());
        }

        $this->mimeTypeValidator->validate($bucket, $mimeType);

        $uploadId = $this->multipartStorage->initiateMultipartUpload($bucket, $objectPath, $mimeType);

        $session = MultipartUploadSession::initiate(
            uploadId: $uploadId,
            documentId: $documentId,
            ownerId: $ownerId,
            bucketName: $bucket,
            objectPath: $objectPath,
            originalName: $command->originalName,
            mimeType: $mimeType,
            totalSize: $command->totalSize,
        );

        $this->sessionRepository->save($session);

        return new InitiateMultipartUploadResult(
            uploadId: $uploadId,
            documentId: $documentId->value(),
        );
    }
}
