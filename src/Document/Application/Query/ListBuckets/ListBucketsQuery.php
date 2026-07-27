<?php

declare(strict_types=1);

namespace App\Document\Application\Query\ListBuckets;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<ListBucketsResult> */
final class ListBucketsQuery implements Query, AuthorizedMessage
{
    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
