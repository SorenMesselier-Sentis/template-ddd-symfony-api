<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;
use App\User\Domain\ValueObject\EmailVerificationTokenId;

final class EmailVerificationTokenIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'email_verification_token_id';
    }

    protected static function uuidClass(): string
    {
        return EmailVerificationTokenId::class;
    }
}
