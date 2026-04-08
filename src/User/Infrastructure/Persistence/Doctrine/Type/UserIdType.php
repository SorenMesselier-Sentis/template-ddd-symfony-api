<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;
use App\User\Domain\ValueObject\UserId;

final class UserIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'user_id';
    }

    protected static function uuidClass(): string
    {
        return UserId::class;
    }
}
