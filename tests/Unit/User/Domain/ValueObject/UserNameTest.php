<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\InvalidUserNameException;
use App\User\Domain\ValueObject\UserName;

final class UserNameTest extends UnitTestCase
{
    public function testItCreatesAValidName(): void
    {
        $name = UserName::fromString('John');

        $this->assertEquals('john', $name->value());
    }

    public function testItTrimsWhitespace(): void
    {
        $name = UserName::fromString('  John  ');

        $this->assertEquals('john', $name->value());
    }

    public function testItThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString('');
    }

    public function testItThrowsOnNameTooLong(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString(str_repeat('a', 31));
    }

    public function testItThrowsOnNameContainsSpecialChars(): void
    {
        $this->expectException(InvalidUserNameException::class);

        UserName::fromString('zed4(cs1-');
    }
}
