<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;

final class UserMother
{
    public static function create(
        ?UserId $id = null,
        ?UserName $firstName = null,
        ?UserName $lastName = null,
        ?Email $email = null,
        ?HashedPassword $password = null,
    ): User {
        return User::create(
            id: $id ?? UserIdMother::random(),
            firstName: $firstName ?? UserNameMother::random(),
            lastName: $lastName ?? UserNameMother::random(),
            email: $email ?? EmailMother::random(),
            password: $password ?? HashedPasswordMother::create(),
        );
    }
}
