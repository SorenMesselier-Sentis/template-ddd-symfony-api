<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\MultipartUpload\AbortMultipartUploadCommand;
use App\Document\Application\Command\MultipartUpload\AbortMultipartUploadResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents/multipart/{uploadId}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/documents/multipart/{uploadId}',
    operationId: 'deleteDocumentsMultipart',
    summary: 'Abort a multipart upload',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Multipart upload aborted')]
final class AbortMultipartUploadController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $uploadId): JsonResponse
    {
        /** @var AbortMultipartUploadResult $result */
        $result = $this->commandBus->dispatch(new AbortMultipartUploadCommand(
            uploadId: $uploadId,
        ));

        return $this->apiResponse->success([
            'uploadId' => $result->uploadId,
            'status' => $result->status,
        ]);
    }
}
