<?php

declare(strict_types=1);

namespace App\User\Application\Command\RefreshToken;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Application\Command\LoginUser\LoginUserResponse;

/** @implements Command<LoginUserResponse> */
final class RefreshTokenCommand implements Command
{
    public function __construct(
        public readonly string $refreshToken,
    ) {
    }
}
