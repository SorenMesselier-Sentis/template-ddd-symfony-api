<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Type;

use App\Document\Domain\Enum\MultipartUploadStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MultipartUploadStatusType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'multipart_upload_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(20)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?MultipartUploadStatus
    {
        if (null === $value) {
            return null;
        }

        return MultipartUploadStatus::from(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof MultipartUploadStatus) {
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
