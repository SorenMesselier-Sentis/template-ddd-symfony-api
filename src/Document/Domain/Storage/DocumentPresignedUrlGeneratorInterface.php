<?php

declare(strict_types=1);

namespace App\Document\Domain\Storage;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\PresignedUrl;

interface DocumentPresignedUrlGeneratorInterface
{
    public function generatePresignedDownloadUrl(Document $document, int $ttlSeconds): PresignedUrl;
}
