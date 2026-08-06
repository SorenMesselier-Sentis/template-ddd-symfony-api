<?php

declare(strict_types=1);

namespace App\Project\Domain\Security;

use App\Project\Domain\ValueObject\OwnerId;

interface OwnerContextInterface
{
    public function ownerId(): OwnerId;

    /**
     * @return list<string>
     */
    public function roles(): array;

    public function isAuthenticated(): bool;
}
