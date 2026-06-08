<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\ValueObject\DocumentId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;

final class DocumentIdType extends AbstractUuidType
{
    protected static function typeName(): string
    {
        return 'document_id';
    }

    protected static function uuidClass(): string
    {
        return DocumentId::class;
    }
}
