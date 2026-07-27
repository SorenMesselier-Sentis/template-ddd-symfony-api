<?php

declare(strict_types=1);

namespace App\Document\Application\Command\DeleteDocument;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<DeleteDocumentResult> */
final class DeleteDocumentCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $documentId,
        public readonly bool $purge = false,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
