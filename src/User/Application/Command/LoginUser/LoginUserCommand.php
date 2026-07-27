<?php

declare(strict_types=1);

namespace App\User\Application\Command\LoginUser;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\ValueObject\Email;

/** @implements Command<LoginUserResponse> */
final class LoginUserCommand implements Command
{
    public function __construct(
        public readonly Email $email,
        public readonly string $password,
    ) {
    }
}
