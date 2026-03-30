<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiResponse
{

    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return new JsonResponse(
            data: $this->normalize(['data' => $data]),
            status: $status,
            json: true,
        );
    }

    public static function created(mixed $data = null):JsonResponse
    {
        return new JsonResponse(
            data: $this->normalize(['data' => $data]),
            status: 201,
            json: true,
        );
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
        return new JsonResponse(
            data: $this->normalize([
                'data' => $data,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'pages' => (int) ceil($total / $perPage),
                ],
            ]),
            json: true,
        );
    }

    private function normalize(mixed $data): string
    {
        return $this->serializer->serialize($data, 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
    }
}
