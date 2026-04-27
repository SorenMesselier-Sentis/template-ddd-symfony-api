<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\User\Domain\ValueObject\HashedPassword;

final class HashedPasswordMother
{
    public static function create(string $plain = 'secret1234'): HashedPassword
    {
        return HashedPassword::fromPlainPassword($plain);
    }
}
