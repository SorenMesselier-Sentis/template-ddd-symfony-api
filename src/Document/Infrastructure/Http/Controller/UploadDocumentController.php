<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\UploadDocument\UploadDocumentCommand;
use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Document\Infrastructure\Http\Response\DocumentResponseData;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/documents', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/documents',
    operationId: 'postDocuments',
    summary: 'Upload a document',
    description: 'Uploads a single-part file to MinIO and persists document metadata. Requires authentication.',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(
            required: ['file', 'bucket'],
            properties: [
                new OA\Property(property: 'file', type: 'string', format: 'binary'),
                new OA\Property(property: 'bucket', type: 'string', example: 'documents'),
            ],
        ),
    ),
)]
#[OA\Response(
    response: 201,
    description: 'Document uploaded',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'originalName', type: 'string'),
                    new OA\Property(property: 'size', type: 'integer'),
                    new OA\Property(property: 'mimeType', type: 'string'),
                    new OA\Property(property: 'bucket', type: 'string'),
                    new OA\Property(property: 'ownerId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 401, description: 'Unauthorized')]
#[OA\Response(response: 404, description: 'Bucket not found')]
#[OA\Response(response: 422, description: 'Validation error')]
final class UploadDocumentController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $bucket = $request->request->getString('bucket');

        if (!$file instanceof UploadedFile) {
            throw new BadRequestHttpException('A file is required.');
        }

        if ('' === $bucket) {
            throw new BadRequestHttpException('A bucket is required.');
        }

        $id = Uuid::v4()->toRfc4122();
        $content = (string) file_get_contents($file->getPathname());
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        /** @var UploadDocumentResult $result */
        $result = $this->commandBus->dispatch(new UploadDocumentCommand(
            id: $id,
            bucket: $bucket,
            originalName: $file->getClientOriginalName(),
            content: $content,
            size: false !== $file->getSize() ? $file->getSize() : strlen($content),
            mimeType: $mimeType,
        ));

        return $this->apiResponse->created(DocumentResponseData::fromUploadResult($result));
    }
}
