<?php

declare(strict_types=1);

namespace App\Document\Application\Command\UploadDocument;

final readonly class UploadDocumentResult
{
    public function __construct(
        public string $id,
        public string $originalName,
        public int $size,
        public string $mimeType,
        public string $bucket,
        public string $objectPath,
        public string $ownerId,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
