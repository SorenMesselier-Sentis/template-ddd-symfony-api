<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

final readonly class InitiateMultipartUploadResult
{
    public function __construct(
        public string $uploadId,
        public string $documentId,
    ) {
    }
}
