<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

final class AwsS3ResultHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(mixed $result): array
    {
        if (\is_array($result)) {
            /** @var array<string, mixed> */
            return $result;
        }

        if (\is_object($result) && method_exists($result, 'toArray')) {
            $array = $result->toArray();

            if (\is_array($array)) {
                /** @var array<string, mixed> */
                return $array;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (!\is_string($value)) {
            throw new \RuntimeException(sprintf('S3 response missing string key "%s".', $key));
        }

        return $value;
    }
}
