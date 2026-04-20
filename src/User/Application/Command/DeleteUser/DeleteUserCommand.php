<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Domain\Bus\Command\Command;

final class DeleteUserCommand implements Command
{
    public function __construct(
        public readonly string $id,
    ) {}
}
