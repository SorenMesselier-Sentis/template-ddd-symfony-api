<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

final class InitiateMultipartUploadCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $bucket,
        public readonly string $originalName,
        public readonly int $totalSize,
        public readonly string $mimeType,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
