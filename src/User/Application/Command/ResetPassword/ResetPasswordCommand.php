<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResetPassword;

use App\Shared\Domain\Bus\Command\Command;

final class ResetPasswordCommand implements Command
{
    public function __construct(
        public readonly string $token,
        public readonly string $password,
    ) {
    }
}
