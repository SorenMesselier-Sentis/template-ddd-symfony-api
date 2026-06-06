<?php

declare(strict_types=1);

namespace App\Document\Application\Query\CheckBucketExists;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

final class CheckBucketExistsQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
