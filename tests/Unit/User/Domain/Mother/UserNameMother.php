<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\User\Domain\ValueObject\UserName;

final class UserNameMother
{
    private const NAMES = ['Jhon', 'Jane', 'Bob', 'Pierre', 'Alice'];

    public static function random(): UserName
    {
        return UserName::fromString(self::NAMES[array_rand(self::NAMES)]);
    }

    public static function create(string $value): UserName
    {
        return UserName::fromString($value);
    }
}
