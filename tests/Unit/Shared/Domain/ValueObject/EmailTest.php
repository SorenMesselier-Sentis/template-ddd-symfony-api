<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\UnitTestCase;

final class EmailTest extends UnitTestCase
{
    public function testItCreatesAValidEmail(): void
    {
        $email = Email::fromString('John.Doe@Example.com');

        $this->assertEquals('john.doe@example.com', $email->value());
    }

    public function testItComparesEquality(): void
    {
        $emailA = Email::fromString('user@example.com');
        $emailB = Email::fromString('USER@example.com');

        $this->assertTrue($emailA->equals($emailB));
    }

    public function testItThrowsOnInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('not-an-email');
    }
}
