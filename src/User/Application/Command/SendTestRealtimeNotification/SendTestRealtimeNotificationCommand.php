<?php

declare(strict_types=1);

namespace App\User\Application\Command\SendTestRealtimeNotification;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class SendTestRealtimeNotificationCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $message,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
