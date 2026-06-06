<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\MultipartUpload\InitiateMultipartUploadCommand;
use App\Document\Application\Command\MultipartUpload\InitiateMultipartUploadResult;
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

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        foreach (['bucket', 'originalName', 'totalSize', 'mimeType'] as $field) {
            if (!isset($payload[$field]) || '' === $payload[$field]) {
                throw new BadRequestHttpException(sprintf('Field "%s" is required.', $field));
            }
        }

        $documentId = Uuid::v4()->toRfc4122();

        /** @var InitiateMultipartUploadResult $result */
        $result = $this->commandBus->dispatch(new InitiateMultipartUploadCommand(
            documentId: $documentId,
            bucket: (string) $payload['bucket'],
            originalName: (string) $payload['originalName'],
            totalSize: (int) $payload['totalSize'],
            mimeType: (string) $payload['mimeType'],
        ));

        return $this->apiResponse->created([
            'uploadId' => $result->uploadId,
            'documentId' => $result->documentId,
        ]);
    }
}
