<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\ValueObject\MimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MimeTypeType extends Type
{
    public const NAME = 'document_mime_type';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(127)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?MimeType
    {
        if (null === $value) {
            return null;
        }

        return MimeType::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof MimeType) {
            return $value->value();
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
