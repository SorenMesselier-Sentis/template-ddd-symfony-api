<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocumentPresignedUrl;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<GetDocumentPresignedUrlResult> */
final class GetDocumentPresignedUrlQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $documentId,
        public readonly ?int $ttlSeconds = null,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
