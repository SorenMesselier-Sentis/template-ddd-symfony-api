<?php

declare(strict_types=1);

namespace App\Document\Application\Privacy;

use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Privacy\PersonalDataEraserInterface;

/**
 * GDPR erasure hard-deletes the document row (see DocumentRepositoryInterface::delete()).
 */
final class DocumentPersonalDataEraser implements PersonalDataEraserInterface
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly DocumentStorageInterface $storage,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function key(): string
    {
        return 'documents';
    }

    public function erase(string $subjectId): void
    {
        $documents = $this->repository->findByOwnerIdIncludingDeleted(OwnerId::fromString($subjectId));

        foreach ($documents as $document) {
            try {
                $this->storage->delete($document->bucketName(), $document->objectPath());
            } catch (\Throwable $exception) {
                // Don't let one missing/unreachable storage object block erasing the rest —
                // the DB row is removed regardless, which is the part GDPR actually requires.
                $this->logger->error('Failed to purge document storage object during erasure', [
                    'exception' => $exception,
                    'documentId' => $document->id()->value(),
                ]);
            }

            $this->repository->delete($document);
        }
    }
}
