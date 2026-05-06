<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Service\TokenGeneratorInterface;
use App\User\Domain\ValueObject\AccessToken;
use App\User\Domain\ValueObject\RefreshToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class JwtTokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly int $accessTokenTtl,
        private readonly int $refreshTokenTtl,
    ) {
    }

    public function generateAccessToken(User $user): AccessToken
    {
        $token = $this->jwtManager->createFromPayload(
            user: new JwtUserAdapter($user),
            payload: [
                'sub' => $user->id()->value(),
                'email' => $user->email()->value(),
            ],
        );

        return new AccessToken($token, $this->accessTokenTtl);
    }

    public function generateRefreshToken(User $user): RefreshToken
    {
        $token = $this->jwtManager->createFromPayload(
            user: new JwtUserAdapter($user),
            payload: [
                'sub' => $user->id()->value(),
                'type' => 'refresh',
            ],
        );

        return new RefreshToken($token, $this->refreshTokenTtl);
    }
}
