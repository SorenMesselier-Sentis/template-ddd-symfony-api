<?php

declare(strict_types=1);

namespace App\Document\Application\Command\CreateBucket;

use App\Document\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

final class CreateBucketCommand implements Command, AuthorizedMessage
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
