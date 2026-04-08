<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Serializer;

use App\Shared\Domain\Bus\Query\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ResponseNormalizer implements NormalizerInterface
{
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {
        if (!$object instanceof Response) {
            throw new \InvalidArgumentException(sprintf('Expected %s, %s given.', Response::class, get_debug_type($object)));
        }

        return get_object_vars($object);
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
