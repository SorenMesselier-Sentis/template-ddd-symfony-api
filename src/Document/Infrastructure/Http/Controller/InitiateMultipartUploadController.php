<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\MultipartUpload\InitiateMultipartUploadCommand;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/documents/multipart', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/documents/multipart',
    operationId: 'postDocumentsMultipart',
    summary: 'Initiate a multipart upload',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['bucket', 'originalName', 'totalSize', 'mimeType'],
        properties: [
            new OA\Property(property: 'bucket', type: 'string', example: 'documents'),
            new OA\Property(property: 'originalName', type: 'string', example: 'large-video.mp4'),
            new OA\Property(property: 'totalSize', type: 'integer', example: 157286400),
            new OA\Property(property: 'mimeType', type: 'string', example: 'application/pdf'),
        ],
    ),
)]
#[OA\Response(response: 201, description: 'Multipart upload initiated')]
final class InitiateMultipartUploadController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        $payload = self::normalizePayload($payload);
        $bucket = self::requiredString($payload, 'bucket');
        $originalName = self::requiredString($payload, 'originalName');
        $mimeType = self::requiredString($payload, 'mimeType');
        $totalSize = self::requiredPositiveInt($payload, 'totalSize');

        $documentId = Uuid::v4()->toRfc4122();

        $result = $this->commandBus->dispatch(new InitiateMultipartUploadCommand(
            documentId: $documentId,
            bucket: $bucket,
            originalName: $originalName,
            totalSize: $totalSize,
            mimeType: $mimeType,
        ));

        return $this->apiResponse->created([
            'uploadId' => $result->uploadId,
            'documentId' => $result->documentId,
        ]);
    }

    /**
     * @param array<mixed, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function normalizePayload(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (!\is_string($key)) {
                throw new BadRequestHttpException('Invalid JSON payload.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requiredString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (!\is_string($value) || '' === $value) {
            throw new BadRequestHttpException(sprintf('Field "%s" is required.', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requiredPositiveInt(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;

        if (\is_int($value) && $value > 0) {
            return $value;
        }

        if (\is_string($value) && '' !== $value && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        throw new BadRequestHttpException(sprintf('Field "%s" is required.', $field));
    }
}
