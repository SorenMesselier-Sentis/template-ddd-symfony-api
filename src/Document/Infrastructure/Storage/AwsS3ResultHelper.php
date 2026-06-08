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
            return self::normalizeArray($result);
        }

        if (\is_object($result) && method_exists($result, 'toArray')) {
            $array = $result->toArray();

            if (\is_array($array)) {
                return self::normalizeArray($array);
            }
        }

        return [];
    }

    /**
     * @param array<mixed, mixed> $array
     *
     * @return array<string, mixed>
     */
    private static function normalizeArray(array $array): array
    {
        $normalized = [];

        foreach ($array as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
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
