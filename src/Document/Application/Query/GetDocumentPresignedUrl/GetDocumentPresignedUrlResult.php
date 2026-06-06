<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocumentPresignedUrl;

final readonly class GetDocumentPresignedUrlResult
{
    public function __construct(
        public string $documentId,
        public string $presignedUrl,
        public int $expiresIn,
        public string $expiresAt,
    ) {
    }
}
