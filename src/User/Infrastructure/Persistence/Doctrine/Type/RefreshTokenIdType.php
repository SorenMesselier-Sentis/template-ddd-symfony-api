<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;
use App\User\Domain\ValueObject\RefreshTokenId;

final class RefreshTokenIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'refresh_token_id';
    }

    protected static function uuidClass(): string
    {
        return RefreshTokenId::class;
    }
}
