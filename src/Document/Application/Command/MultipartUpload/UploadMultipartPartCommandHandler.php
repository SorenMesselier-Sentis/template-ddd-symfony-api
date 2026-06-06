<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Service\MultipartPartSizeValidator;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use App\Document\Domain\ValueObject\PartNumber;
use Aws\S3\Exception\S3Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UploadMultipartPartCommandHandler
{
    public function __construct(
        private readonly MultipartUploadSessionRepositoryInterface $sessionRepository,
        private readonly MultipartDocumentStorageInterface $multipartStorage,
        private readonly MultipartPartSizeValidator $partSizeValidator,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(UploadMultipartPartCommand $command): UploadMultipartPartResult
    {
        $session = $this->sessionRepository->findActiveByUploadIdAndOwner(
            $command->uploadId,
            $this->ownerContext->ownerId(),
        );

        if (null === $session) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        $partNumber = PartNumber::fromInt($command->partNumber);
        $this->partSizeValidator->validate($command->size, $command->isLastPart);

        try {
            $etag = $this->multipartStorage->uploadPart(
                bucket: $session->bucketName(),
                objectPath: $session->objectPath(),
                uploadId: $session->uploadId(),
                partNumber: $partNumber,
                content: $command->content,
            );
        } catch (S3Exception) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        $session->registerPart($partNumber, $etag, $command->size);
        $this->sessionRepository->save($session);

        return new UploadMultipartPartResult(
            etag: $etag,
            partNumber: $partNumber->value(),
        );
    }
}
