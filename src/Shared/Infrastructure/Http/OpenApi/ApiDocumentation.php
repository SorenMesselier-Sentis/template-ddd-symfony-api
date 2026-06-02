<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\OpenApi;

/**
 * OpenAPI path prefixes. Use these constants in #[OA\*] path arguments (PHP allows class const in attributes).
 */
final class ApiDocumentation
{
    public const V1 = '/api/v1';
}
