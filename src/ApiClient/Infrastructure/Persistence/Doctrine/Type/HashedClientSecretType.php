<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Persistence\Doctrine\Type;

use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\DoctrineStringValueTypeTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class HashedClientSecretType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'hashed_client_secret';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HashedClientSecret
    {
        if (null === $value) {
            return null;
        }

        return HashedClientSecret::fromHash(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof HashedClientSecret) {
            return $value->value();
        }

        return self::assertString($value, self::NAME);
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
