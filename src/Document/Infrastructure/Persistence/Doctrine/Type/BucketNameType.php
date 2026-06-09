<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\ValueObject\BucketName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class BucketNameType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'bucket_name';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(63)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BucketName
    {
        if (null === $value) {
            return null;
        }

        return BucketName::fromString(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof BucketName) {
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
