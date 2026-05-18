<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\RefreshTokenMother;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;

final class RefreshTokenTest extends UnitTestCase
{
    public function testItCreatesAValidRefreshToken(): void
    {
        $userId = UserIdMother::random();
        $token = RefreshTokenMother::create(userId: $userId, token: 'my-token');

        $this->assertEquals('my-token', $token->token());
        $this->assertTrue($userId->equals($token->userId()));
        $this->assertFalse($token->isRevoked());
        $this->assertFalse($token->isExpired());
    }

    public function testItRevokesToken(): void
    {
        $token = RefreshTokenMother::create();

        $token->revoke();

        $this->assertTrue($token->isRevoked());
    }

    public function testItDetectsExpiredToken(): void
    {
        $token = RefreshTokenMother::expired();

        $this->assertTrue($token->isExpired());
    }
}
