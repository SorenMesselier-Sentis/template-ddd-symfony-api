<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocuments;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<DocumentsCursorResponse> */
final class GetDocumentsCursorQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly Filters $filters,
        public readonly CursorPagination $cursorPagination,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
