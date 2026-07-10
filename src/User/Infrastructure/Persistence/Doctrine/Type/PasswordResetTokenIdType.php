<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;
use App\User\Domain\ValueObject\PasswordResetTokenId;

final class PasswordResetTokenIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'password_reset_token_id';
    }

    protected static function uuidClass(): string
    {
        return PasswordResetTokenId::class;
    }
}
