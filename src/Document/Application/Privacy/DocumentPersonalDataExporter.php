<?php

declare(strict_types=1);

namespace App\Document\Application\Privacy;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Privacy\PersonalDataExporterInterface;

final class DocumentPersonalDataExporter implements PersonalDataExporterInterface
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
    ) {
    }

    public function key(): string
    {
        return 'documents';
    }

    public function export(string $subjectId): array
    {
        $documents = $this->repository->findByOwnerId(OwnerId::fromString($subjectId));

        return array_map(static fn (Document $document): array => [
            'id' => $document->id()->value(),
            'original_name' => $document->originalName(),
            'bucket' => $document->bucketName()->value(),
            'mime_type' => $document->mimeType()->value(),
            'size' => $document->size(),
            'status' => $document->status()->value,
            'created_at' => $document->createdAt()->format(\DateTimeInterface::ATOM),
        ], $documents);
    }
}
