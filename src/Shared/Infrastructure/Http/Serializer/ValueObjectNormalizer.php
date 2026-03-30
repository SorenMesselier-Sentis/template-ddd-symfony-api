<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Serializer;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ValueObjectNormalizer implements NormalizerInterface
{
    public function normalize(
        mixed $object,
        string $format = null,
        array $context = []
    ): string
    {
        return $object->value();
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Uuid || $data instanceof Email;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Uuid::class => true,
            Email::class => true,
        ];
    }
}
