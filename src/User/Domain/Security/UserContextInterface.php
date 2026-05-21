<?php

declare(strict_types=1);

namespace App\User\Domain\Security;

use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRole;

interface UserContextInterface
{
    public function userId(): UserId;

    /**
     * @return list<UserRole>
     */
    public function roles(): array;

    public function isAuthenticated(): bool;
}
