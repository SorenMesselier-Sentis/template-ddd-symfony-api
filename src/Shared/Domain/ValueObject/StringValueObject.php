<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Marker for value objects that serialize to a single string primitive.
 */
interface StringValueObject
{
    public function value(): string;
}
