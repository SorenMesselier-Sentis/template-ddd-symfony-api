<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\DoctrineStringValueTypeTrait;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class WebhookSubscriptionStatusType extends Type
{
    use DoctrineStringValueTypeTrait;

    public const NAME = 'webhook_subscription_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(20)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?WebhookSubscriptionStatus
    {
        if (null === $value) {
            return null;
        }

        return WebhookSubscriptionStatus::from(self::assertString($value, self::NAME));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof WebhookSubscriptionStatus) {
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
