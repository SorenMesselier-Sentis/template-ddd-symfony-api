<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\MultipartUpload\CompleteMultipartUploadCommand;
use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents/multipart/{uploadId}/complete', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/documents/multipart/{uploadId}/complete',
    operationId: 'postDocumentsMultipartComplete',
    summary: 'Complete a multipart upload',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 201, description: 'Multipart upload completed')]
final class CompleteMultipartUploadController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $uploadId): JsonResponse
    {
        /** @var UploadDocumentResult $result */
        $result = $this->commandBus->dispatch(new CompleteMultipartUploadCommand(
            uploadId: $uploadId,
        ));

        return $this->apiResponse->created([
            'id' => $result->id,
            'originalName' => $result->originalName,
            'size' => $result->size,
            'mimeType' => $result->mimeType,
            'bucket' => $result->bucket,
            'objectPath' => $result->objectPath,
            'ownerId' => $result->ownerId,
            'status' => $result->status,
            'createdAt' => $result->createdAt,
            'updatedAt' => $result->updatedAt,
        ]);
    }
}
