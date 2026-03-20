<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\Shared\Domain\Bus\Command\Command;

final class CreateUserCommand implements Command
{
    public function __construct(
        public readonly string $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
