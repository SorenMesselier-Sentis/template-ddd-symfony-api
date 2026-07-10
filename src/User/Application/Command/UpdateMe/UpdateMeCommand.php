<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateMe;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

final class UpdateMeCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
