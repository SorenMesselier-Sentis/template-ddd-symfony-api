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
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
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
                    static fn (UserRole $role): string => $role->value,
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
                // Without a per-token nonce, two refresh tokens issued for the same user
                // within the same second are byte-identical (Lexik's own iat/exp are also
                // second-precision and otherwise the only variable claims), which collides
                // with refresh_tokens' unique `token` column. jti is ignored by
                // TokenClaims::fromRefreshTokenPayload (unknown claims pass through), so this
                // is purely a uniqueness guarantee, not a claim anything decodes.
                'jti' => bin2hex(random_bytes(16)),
            ],
        );

        return new RefreshToken($token, $this->refreshTokenTtl);
    }

    public function decodeAccessToken(string $token): TokenClaims
    {
        return TokenClaims::fromAccessTokenPayload($this->parsePayload($token));
    }

    public function decodeRefreshToken(string $token): TokenClaims
    {
        $payload = $this->parsePayload($token);

        if (!isset($payload['type']) || 'refresh' !== $payload['type']) {
            throw InvalidTokenException::create();
        }

        return TokenClaims::fromRefreshTokenPayload($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePayload(string $token): array
    {
        try {
            return $this->normalizePayload($this->jwtManager->parse($token));
        } catch (ExpiredTokenException) {
            throw TokenExpiredException::create();
        } catch (LexikInvalidTokenException) {
            throw InvalidTokenException::create();
        } catch (JWTDecodeFailureException $e) {
            if (JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()) {
                throw TokenExpiredException::create();
            }

            throw InvalidTokenException::create();
        }
    }

    /**
     * @param array<mixed, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $claim => $value) {
            if (!\is_string($claim) || '' === $claim) {
                throw InvalidTokenException::create();
            }

            $normalized[$claim] = $value;
        }

        return $normalized;
    }
}
