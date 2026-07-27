<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<UploadMultipartPartResult> */
final class UploadMultipartPartCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $uploadId,
        public readonly int $partNumber,
        public readonly string $content,
        public readonly int $size,
        public readonly bool $isLastPart,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
