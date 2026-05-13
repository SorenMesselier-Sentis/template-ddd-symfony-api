<?php

declare(strict_types=1);

namespace App\User\Application\Command\RefreshToken;

use App\Shared\Domain\Bus\Command\Command;

final class RefreshTokenCommand implements Command
{
    public function __construct(
        public readonly string $refreshToken,
    ) {
    }
}
