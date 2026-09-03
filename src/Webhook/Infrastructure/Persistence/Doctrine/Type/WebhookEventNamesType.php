<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps a JSON array of domain event-name strings (e.g. "document.uploaded") — the subscription's
 * filter list, matched against DomainEvent::eventName() in DispatchWebhooksOnAnyDomainEvent.
 *
 * Stored as a plain text column, not a native JSON column: DoctrineWebhookSubscriptionRepository::
 * findActiveByEventName() filters with a LIKE against the JSON-encoded value (see that method's
 * docblock), and PostgreSQL's `json`/`jsonb` types don't support the `LIKE` operator without an
 * explicit cast — a text column sidesteps that entirely and keeps the query portable.
 */
final class WebhookEventNamesType extends Type
{
    public const NAME = 'webhook_event_names';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    /**
     * @return list<string>|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $contents = \stream_get_contents($value);
            if (false === $contents) {
                throw ValueNotConvertible::new('', self::NAME, 'Unable to read event names stream.');
            }
            $value = $contents;
        }

        if (\is_string($value)) {
            try {
                $decoded = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
            }
        } elseif (\is_array($value)) {
            $decoded = $value;
        } else {
            throw ValueNotConvertible::new($value, self::NAME, 'Expected a JSON string or array of event name strings.');
        }

        if (!\is_array($decoded)) {
            throw ValueNotConvertible::new($value, self::NAME, 'Event names JSON must decode to a list.');
        }

        $eventNames = [];
        foreach ($decoded as $item) {
            if (!\is_string($item)) {
                throw ValueNotConvertible::new($value, self::NAME, 'Each event name must be a string.');
            }
            $eventNames[] = $item;
        }

        return $eventNames;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_array($value)) {
            throw ValueNotConvertible::new($value, self::NAME, 'Expected a list of event name strings.');
        }

        try {
            return \json_encode(array_values($value), \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw SerializationFailed::new($value, 'json', $e->getMessage(), $e);
        }
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
