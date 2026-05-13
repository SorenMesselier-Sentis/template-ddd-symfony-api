<?php

declare(strict_types=1);

namespace App\User\Application\Command\LogoutUser;

use App\Shared\Domain\Bus\Command\Command;

final class LogoutUserCommand implements Command
{
    public function __construct(
        public readonly string $refreshToken,
    ) {}
}
