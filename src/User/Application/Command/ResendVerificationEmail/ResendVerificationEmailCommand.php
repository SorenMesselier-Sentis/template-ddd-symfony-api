<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResendVerificationEmail;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

final class ResendVerificationEmailCommand implements Command, AuthorizedMessage
{
    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
