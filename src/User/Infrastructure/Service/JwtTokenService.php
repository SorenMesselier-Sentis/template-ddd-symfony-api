<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\AccessToken;
use App\User\Domain\ValueObject\RefreshToken;
use App\User\Domain\ValueObject\TokenClaims;
use App\User\Domain\ValueObject\UserRole;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException as LexikInvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class JwtTokenService implements TokenServiceInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly int $accessTokenTtl,
        private readonly int $refreshTokenTtl,
    ) {
    }

    public function generateAccessToken(User $user): AccessToken
    {
        $issuedAt = time();

        $token = $this->jwtManager->createFromPayload(
            user: new JwtUserAdapter($user),
            payload: [
                'sub' => $user->id()->value(),
                'email' => $user->email()->value(),
                'roles' => array_map(
                    fn(UserRole $role) => $role->value,
                    $user->roles(),
                ),
                'iat' => $issuedAt,
                'exp' => $issuedAt + $this->accessTokenTtl,
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

    public function decodeAccessToken(string $token): TokenClaims
    {
        try {
            $payload = $this->jwtManager->parse($token);
        } catch (ExpiredTokenException) {
            throw TokenExpiredException::create();
        } catch (LexikInvalidTokenException) {
            throw InvalidTokenException::create();
        }

        return new TokenClaims(
            sub: $payload['sub'],
            email: $payload['email'],
            roles: $payload['roles'],
            iat: $payload['iat'],
            exp: $payload['exp'],
        );
    }
}
