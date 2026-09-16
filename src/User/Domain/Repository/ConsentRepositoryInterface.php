<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\Consent;
use App\User\Domain\ValueObject\ConsentType;
use App\User\Domain\ValueObject\UserId;

interface ConsentRepositoryInterface
{
    public function save(Consent $consent): void;

    public function findByUserIdAndType(UserId $userId, ConsentType $type): ?Consent;

    /** @return array<int, Consent> */
    public function findByUserId(UserId $userId): array;
}
