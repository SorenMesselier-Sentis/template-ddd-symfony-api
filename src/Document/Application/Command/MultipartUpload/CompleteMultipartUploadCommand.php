<?php

declare(strict_types=1);

namespace App\Document\Application\Command\MultipartUpload;

use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<UploadDocumentResult> */
final class CompleteMultipartUploadCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $uploadId,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
