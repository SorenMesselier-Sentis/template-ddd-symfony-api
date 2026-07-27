<?php

declare(strict_types=1);

namespace App\User\Application\Command\RequestPasswordReset;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\ValueObject\Email;

/** @implements Command<null> */
final class RequestPasswordResetCommand implements Command
{
    public function __construct(
        public readonly Email $email,
    ) {
    }
}
