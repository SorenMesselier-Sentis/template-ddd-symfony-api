<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Serializer;

use App\Shared\Domain\ValueObject\StringValueObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ValueObjectNormalizer implements NormalizerInterface
{
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = [],
    ): string {
        if (!$object instanceof StringValueObject) {
            throw new \InvalidArgumentException(sprintf('Expected %s, %s given.', StringValueObject::class, \get_debug_type($object)));
        }

        return $object->value();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof StringValueObject;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            StringValueObject::class => true,
        ];
    }
}
