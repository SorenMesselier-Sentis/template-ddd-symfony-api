<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps a JSON array of OAuth2 scope strings — used both by ApiClient.scopes (granted scopes)
 * and IssuedAccessToken.scopes (scopes actually issued on that token).
 */
final class ApiClientScopesType extends Type
{
    public const NAME = 'api_client_scopes';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
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
                throw ValueNotConvertible::new('', self::NAME, 'Unable to read scopes stream.');
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
            throw ValueNotConvertible::new($value, self::NAME, 'Expected a JSON string or array of scope strings.');
        }

        if (!\is_array($decoded)) {
            throw ValueNotConvertible::new($value, self::NAME, 'Scopes JSON must decode to a list.');
        }

        $scopes = [];
        foreach ($decoded as $item) {
            if (!\is_string($item)) {
                throw ValueNotConvertible::new($value, self::NAME, 'Each scope must be a string.');
            }
            $scopes[] = $item;
        }

        return $scopes;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_array($value)) {
            throw ValueNotConvertible::new($value, self::NAME, 'Expected a list of scope strings.');
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
