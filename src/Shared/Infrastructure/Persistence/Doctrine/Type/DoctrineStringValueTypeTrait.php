<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

trait DoctrineStringValueTypeTrait
{
    private static function assertString(mixed $value, string $typeName): string
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Expected string database value for type "%s", got "%s".', $typeName, \get_debug_type($value)));
        }

        return $value;
    }
}
