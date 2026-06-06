<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Response;

use App\Shared\Infrastructure\Http\Pagination\PaginationLinkBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiResponse
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly PaginationLinkBuilder $paginationLinkBuilder,
    ) {
    }

    public function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return new JsonResponse(
            data: $this->normalize(['data' => $data]),
            status: $status,
            json: true,
        );
    }

    public function created(mixed $data = null): JsonResponse
    {
        return new JsonResponse(
            data: $this->normalize(['data' => $data]),
            status: 201,
            json: true,
        );
    }

    public function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    public function paginated(
        mixed $data,
        int $total,
        int $page,
        int $limit,
        Request $request,
    ): JsonResponse {
        $totalPages = PaginationMeta::totalPages($total, $limit);

        return new JsonResponse(
            data: $this->normalize([
                'data' => $data,
                'meta' => PaginationMeta::from($total, $page, $limit),
                'links' => $this->paginationLinkBuilder->build($request, $page, $limit, $totalPages),
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
