<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\ValueObject\ObjectPath;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class ObjectPathType extends Type
{
    public const NAME = 'object_path';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(1024)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ObjectPath
    {
        if (null === $value) {
            return null;
        }

        return ObjectPath::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ObjectPath) {
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
