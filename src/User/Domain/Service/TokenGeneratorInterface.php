<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\AccessToken;
use App\User\Domain\ValueObject\RefreshToken;

interface TokenGeneratorInterface
{
    public function generateAccessToken(User $user): AccessToken;

    public function generateRefreshToken(User $user): RefreshToken;
}
