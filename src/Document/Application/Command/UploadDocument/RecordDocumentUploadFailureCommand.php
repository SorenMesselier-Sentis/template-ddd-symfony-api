<?php

declare(strict_types=1);

namespace App\Document\Application\Command\UploadDocument;

use App\Shared\Domain\Bus\Command\Command;

final class RecordDocumentUploadFailureCommand implements Command
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $ownerId,
        public readonly string $bucket,
        public readonly string $objectPath,
        public readonly string $originalName,
        public readonly int $size,
        public readonly string $mimeType,
    ) {
    }
}
