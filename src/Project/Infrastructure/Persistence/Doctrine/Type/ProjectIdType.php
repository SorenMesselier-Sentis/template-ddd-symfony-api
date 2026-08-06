<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\Doctrine\Type;

use App\Project\Domain\ValueObject\ProjectId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\DoctrineStringValueTypeTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class ProjectIdType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'project_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'UUID';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ProjectId
    {
        if (null === $value) {
            return null;
        }

        return ProjectId::fromString(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ProjectId) {
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
