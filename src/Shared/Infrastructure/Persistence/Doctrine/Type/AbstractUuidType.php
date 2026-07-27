<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Base class to map a SQL UUID field to a concrete subclass of {@see Uuid}
 * (e.g. UserId). Each subclass must define {@see typeName()} and {@see uuidClass()}.
 *
 * Register each type in config/packages/doctrine.yaml (dbal.types section).
 */
abstract class AbstractUuidType extends Type
{
    use DoctrineStringValueTypeTrait;

    /**
     * Doctrine unique type name.
     */
    abstract protected static function typeName(): string;

    /**
     * @return class-string<Uuid>
     */
    abstract protected static function uuidClass(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'UUID';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        if (null === $value) {
            return null;
        }

        $class = static::uuidClass();

        if ($value instanceof $class) {
            return $value;
        }

        return $class::fromString(self::assertString($value, static::typeName()));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value->value();
        }

        return self::assertString($value, static::typeName());
    }

    public function getName(): string
    {
        return static::typeName();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
