<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\DoctrineStringValueTypeTrait;
use App\User\Domain\ValueObject\UserStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class UserStatusType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'user_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(20)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserStatus
    {
        if (null === $value) {
            return null;
        }

        return UserStatus::from(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof UserStatus) {
            return $value->value;
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
