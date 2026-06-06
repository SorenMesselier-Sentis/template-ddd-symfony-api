<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;

final class OwnerIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'owner_id';
    }

    protected static function uuidClass(): string
    {
        return OwnerId::class;
    }
}
