<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

final readonly class UploadMultipartPartResult
{
    public function __construct(
        public string $etag,
        public int $partNumber,
    ) {
    }
}
