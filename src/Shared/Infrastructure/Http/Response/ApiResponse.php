<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
        ], $status);
    }

    public static function created(mixed $data = null):JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
        ], 201);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    public static function paginated(
        mixed $data,
        int $total,
        int $page,
        int $perPage,
    ): JsonResponse {
        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
