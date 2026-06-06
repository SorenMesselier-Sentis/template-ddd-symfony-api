<?php

declare(strict_types=1);

namespace App\Document\Application\Command\DeleteDocument;

final class DeleteDocumentResult
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $status,
        public readonly bool $purged,
        public readonly string $updatedAt,
    ) {
    }
}
