<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\User\Domain\Entity\RefreshToken;
use App\User\Domain\ValueObject\RefreshTokenId;
use App\User\Domain\ValueObject\UserId;

final class RefreshTokenMother
{
    public static function create(
        ?RefreshTokenId $id = null,
        ?UserId $userId = null,
        ?string $token = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): RefreshToken {
        return RefreshToken::create(
            id: $id ?? RefreshTokenId::random(),
            userId: $userId ?? UserIdMother::random(),
            token: $token ?? 'refresh-token-'.uniqid(),
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+1 hour'),
        );
    }

    public static function expired(): RefreshToken
    {
        return self::create(expiresAt: new \DateTimeImmutable('-1 hour'));
    }
}
