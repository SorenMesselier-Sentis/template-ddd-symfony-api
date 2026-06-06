<?php

declare(strict_types=1);

namespace App\Document\Application\Command\UploadDocument;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

final class UploadDocumentCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $bucket,
        public readonly string $originalName,
        public readonly string $content,
        public readonly int $size,
        public readonly string $mimeType,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
