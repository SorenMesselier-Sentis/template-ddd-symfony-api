<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

use App\Shared\Domain\Bus\Query\Response;
use App\User\Domain\Entity\User;

final class UserResponse implements Response
{

    public readonly string $id;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $email;
    public readonly string $password;

    public function __construct(User $user)
    {
        $this->id = $user->id()->value();
        $this->firstName = $user->firstName()->value();
        $this->lastName = $user->lastName()->value();
        $this->email = $user->email()->value();
    }
}
