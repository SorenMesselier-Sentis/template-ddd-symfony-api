<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AbortMultipartUploadCommandHandler
{
    public function __construct(
        private readonly MultipartUploadSessionRepositoryInterface $sessionRepository,
        private readonly MultipartDocumentStorageInterface $multipartStorage,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(AbortMultipartUploadCommand $command): AbortMultipartUploadResult
    {
        $session = $this->sessionRepository->findActiveByUploadIdAndOwner(
            $command->uploadId,
            $this->ownerContext->ownerId(),
        );

        if (null === $session) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        try {
            $this->multipartStorage->abortMultipartUpload(
                bucket: $session->bucketName(),
                objectPath: $session->objectPath(),
                uploadId: $session->uploadId(),
            );
        } catch (\Throwable) {
            throw UploadSessionNotFoundException::withUploadId($command->uploadId);
        }

        $session->abort();
        $this->sessionRepository->save($session);

        return new AbortMultipartUploadResult(
            uploadId: $session->uploadId(),
            status: $session->status()->value,
        );
    }
}
