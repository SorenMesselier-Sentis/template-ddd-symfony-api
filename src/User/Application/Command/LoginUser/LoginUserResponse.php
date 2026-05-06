<?php

declare(strict_types=1);

namespace App\User\Application\Command\LoginUser;

use App\Shared\Domain\Bus\Query\Response;

final class LoginUserResponse implements Response
{
    public readonly string $accessToken;
    public readonly int $accessTokenExpiresIn;
    public readonly string $refreshToken;
    public readonly int $refreshTokenExpiresIn;
    public readonly string $tokenType;

    public function __construct(
        string $accessToken,
        int $accessTokenExpiresIn,
        string $refreshToken,
        int $refreshTokenExpiresIn,
    ) {
        $this->accessToken = $accessToken;
        $this->accessTokenExpiresIn = $accessTokenExpiresIn;
        $this->refreshToken = $refreshToken;
        $this->refreshTokenExpiresIn = $refreshTokenExpiresIn;
        $this->tokenType = 'Bearer';
    }
}
