<?php

declare(strict_types=1);

namespace App\Document\Domain\Repository;

use App\Document\Domain\Entity\MultipartUploadSession;
use App\Document\Domain\ValueObject\OwnerId;

interface MultipartUploadSessionRepositoryInterface
{
    public function save(MultipartUploadSession $session): void;

    public function findActiveByUploadId(string $uploadId): ?MultipartUploadSession;

    public function findActiveByUploadIdAndOwner(string $uploadId, OwnerId $ownerId): ?MultipartUploadSession;
}
