<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\ValueObject\HashedPassword;

final class HashedPasswordTest extends UnitTestCase
{
    public function testItHashesPlainPassword(): void
    {
        $password = HashedPassword::fromPlainPassword('secret1234');

        $this->assertNotSame('secret1234', $password->value());
        $this->assertTrue($password->verify('secret1234'));
    }

    public function testItVerifiesFromExistingHash(): void
    {
        $hash = password_hash('secret1234', PASSWORD_ARGON2ID);
        $password = HashedPassword::fromHash($hash);

        $this->assertTrue($password->verify('secret1234'));
        $this->assertFalse($password->verify('wrong-password'));
    }
}
