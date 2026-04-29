<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\InvalidUserNameException;
use App\User\Domain\ValueObject\UserName;

final class UserNameTest extends UnitTestCase
{
    public function test_it_creates_a_valid_name(): void
    {
        $name = UserName::fromString('John');

        $this->assertEquals('john', $name->value());
    }

    public function test_it_trims_whitespace(): void
    {
        $name = UserName::fromString('  John  ');

        $this->assertEquals('john', $name->value());
    }

    public function test_it_throws_on_empty_name(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString('');
    }

    public function test_it_throws_on_name_too_long(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString(str_repeat('a', 31));
    }

    public function test_it_throws_on_name_contains_special_chars(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString('zed4(cs1-');
    }
}
