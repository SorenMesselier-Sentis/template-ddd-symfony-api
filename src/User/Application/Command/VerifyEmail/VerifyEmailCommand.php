<?php

declare(strict_types=1);

namespace App\User\Application\Command\VerifyEmail;

use App\Shared\Domain\Bus\Command\Command;

final class VerifyEmailCommand implements Command
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}
