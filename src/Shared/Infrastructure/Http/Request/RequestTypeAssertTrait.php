<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\ValidationError;
use App\Shared\Domain\Exception\ValidationException;

trait RequestTypeAssertTrait
{
    protected static function assertString(mixed $value, string $field): string
    {
        if (!\is_string($value)) {
            throw new ValidationException([new ValidationError(field: $field, code: 'type_mismatch', message: sprintf('Field "%s" must be of type string.', $field))]);
        }

        return $value;
    }

    protected static function assertOptionalString(mixed $value, string $field): ?string
    {
        if (null === $value) {
            return null;
        }

        return self::assertString($value, $field);
    }

    /**
     * @return list<string>
     */
    protected static function assertStringList(mixed $value, string $field): array
    {
        if (!\is_array($value) || [] === $value) {
            throw new ValidationException([new ValidationError(field: $field, code: 'required', message: sprintf('Field "%s" must be a non-empty array.', $field))]);
        }

        $strings = [];

        foreach ($value as $item) {
            if (!\is_string($item)) {
                throw new ValidationException([new ValidationError(field: $field, code: 'type_mismatch', message: sprintf('Field "%s" must contain only strings.', $field))]);
            }

            $strings[] = $item;
        }

        return $strings;
    }
}
