<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Serializer;

use App\Shared\Domain\Bus\Query\Response;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Query Response DTOs are already flattened to scalars/arrays in their own constructors, so this
 * intentionally does not recurse into a generic ObjectNormalizer — but it must still run property
 * names through the app-wide camelCase-to-snake_case name converter (see serializer.yaml), or
 * every response envelope silently ships camelCase keys instead of the documented snake_case
 * convention (only ever noticed on multi-word properties, e.g. createdAt/eventNames — a
 * single-word property like name/status looks fine either way, which is how this stayed latent).
 */
final class ResponseNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly NameConverterInterface $nameConverter,
    ) {
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = [],
    ): array {
        if (!$object instanceof Response) {
            throw new \InvalidArgumentException(sprintf('Expected %s, %s given.', Response::class, get_debug_type($object)));
        }

        $normalized = [];
        foreach (get_object_vars($object) as $property => $value) {
            $normalized[$this->nameConverter->normalize($property, $object::class, $format, $context)] = $value;
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Response;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Response::class => false];
    }
}
