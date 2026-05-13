<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\User\Domain\ValueObject\UserRole;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps a JSON array of Symfony role strings to {@see UserRole} backed enums on the domain entity.
 */
final class UserRolesType extends Type
{
    public const NAME = 'user_roles';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * @return list<UserRole>|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = \stream_get_contents($value);
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
            throw ValueNotConvertible::new($value, self::NAME, 'Expected a JSON string or array of role strings.');
        }

        if (!\is_array($decoded)) {
            throw ValueNotConvertible::new($value, self::NAME, 'Roles JSON must decode to a list.');
        }

        $roles = [];
        foreach ($decoded as $item) {
            if (!\is_string($item)) {
                throw ValueNotConvertible::new($value, self::NAME, 'Each role must be a string.');
            }
            $roles[] = UserRole::from($item);
        }

        return $roles;
    }

    /**
     * @param list<UserRole>|null $value
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_array($value)) {
            throw SerializationFailed::new($value, 'json', 'Expected a list of UserRole.');
        }

        $strings = [];
        foreach ($value as $role) {
            if (!$role instanceof UserRole) {
                throw SerializationFailed::new($value, 'json', 'Each element must be a UserRole enum.');
            }
            $strings[] = $role->value;
        }

        try {
            return \json_encode($strings, \JSON_THROW_ON_ERROR);
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
