<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\DeleteDocument\DeleteDocumentCommand;
use App\Document\Application\Command\DeleteDocument\DeleteDocumentResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents/{id}', requirements: ['id' => '[0-9a-f\-]+'], methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/documents/{id}',
    operationId: 'deleteDocument',
    summary: 'Soft-delete a document',
    description: 'Marks a document as deleted. Optionally purges the object from object storage when purge=true.',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(
    name: 'purge',
    in: 'query',
    required: false,
    description: 'When true, also removes the object from object storage after soft-delete.',
    schema: new OA\Schema(type: 'boolean', default: false),
)]
#[OA\Response(response: 200, description: 'Document deleted')]
#[OA\Response(response: 403, description: 'Forbidden')]
#[OA\Response(response: 404, description: 'Document not found')]
final class DeleteDocumentController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $purge = filter_var($request->query->get('purge', 'false'), FILTER_VALIDATE_BOOLEAN);

        /** @var DeleteDocumentResult $result */
        $result = $this->commandBus->dispatch(new DeleteDocumentCommand(
            documentId: $id,
            purge: $purge,
        ));

        return $this->apiResponse->success([
            'documentId' => $result->documentId,
            'status' => $result->status,
            'purged' => $result->purged,
            'updatedAt' => $result->updatedAt,
        ]);
    }
}
