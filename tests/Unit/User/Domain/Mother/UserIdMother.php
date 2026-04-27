<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\User\Domain\ValueObject\UserId;

final class UserIdMother
{
    public static function random(): UserId
    {
        return UserId::random();
    }

    public function create(string $value): UserId
    {
        return UserId::fromString($value);
    }
}
