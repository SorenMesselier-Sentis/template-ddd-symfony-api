<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

use App\Shared\Domain\Bus\Command\Command;

final class UpdateUserCommand implements Command
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
    ) {
    }
}
