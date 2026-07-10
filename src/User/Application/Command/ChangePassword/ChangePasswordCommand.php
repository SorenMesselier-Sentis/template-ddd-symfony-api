<?php

declare(strict_types=1);

namespace App\User\Application\Command\ChangePassword;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

final class ChangePasswordCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
