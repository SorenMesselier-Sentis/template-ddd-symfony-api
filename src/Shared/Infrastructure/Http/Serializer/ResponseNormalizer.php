<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Serializer;

use App\Shared\Domain\BUS\Query\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class ResponseNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $objectNormalizer
    ) {}

    public function normalize(
        mixed $object,
        string $format = null,
        array $context = [],
    ): array {
        return $this->objectNormalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Response;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Response::class => false];
    }
}
