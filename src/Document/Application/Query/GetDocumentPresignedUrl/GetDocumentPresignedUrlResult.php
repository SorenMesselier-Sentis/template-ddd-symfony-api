<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocumentPresignedUrl;

use App\Shared\Domain\Bus\Query\Response;

final readonly class GetDocumentPresignedUrlResult implements Response
{
    public function __construct(
        public string $documentId,
        public string $presignedUrl,
        public int $expiresIn,
        public string $expiresAt,
    ) {
    }
}
