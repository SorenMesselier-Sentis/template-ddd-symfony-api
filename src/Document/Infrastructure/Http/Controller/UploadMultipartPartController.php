<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\MultipartUpload\UploadMultipartPartCommand;
use App\Document\Application\Command\MultipartUpload\UploadMultipartPartResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents/multipart/{uploadId}/parts/{partNumber}', requirements: ['partNumber' => '\d+'], methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/documents/multipart/{uploadId}/parts/{partNumber}',
    operationId: 'putDocumentsMultipartPart',
    summary: 'Upload a multipart part',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'uploadId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'partNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 10000))]
#[OA\Parameter(name: 'isLastPart', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', default: false))]
#[OA\RequestBody(
    required: true,
    content: new OA\MediaType(
        mediaType: 'application/octet-stream',
        schema: new OA\Schema(type: 'string', format: 'binary'),
    ),
)]
#[OA\Response(response: 200, description: 'Part uploaded')]
final class UploadMultipartPartController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request, string $uploadId, int $partNumber): JsonResponse
    {
        $content = $request->getContent(false);
        $size = strlen($content);

        if (0 === $size) {
            throw new BadRequestHttpException('Part content is required.');
        }

        $isLastPart = filter_var($request->query->get('isLastPart', 'false'), FILTER_VALIDATE_BOOLEAN);

        /** @var UploadMultipartPartResult $result */
        $result = $this->commandBus->dispatch(new UploadMultipartPartCommand(
            uploadId: $uploadId,
            partNumber: $partNumber,
            content: $content,
            size: $size,
            isLastPart: $isLastPart,
        ));

        return $this->apiResponse->success([
            'etag' => $result->etag,
            'partNumber' => $result->partNumber,
        ]);
    }
}
