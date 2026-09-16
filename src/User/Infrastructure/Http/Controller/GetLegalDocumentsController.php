<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Infrastructure\Legal\LegalDocumentVersion;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/legal/documents', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/legal/documents',
    operationId: 'getLegalDocuments',
    summary: 'Current version of each legal document',
    description: 'Public — lets a client detect a stale consent by comparing the version it '
        .'recorded (see POST /users/me/consents) against the current one here. Document content '
        .'itself is not served by the API; see docs/legal/ in the repository.',
    tags: ['Legal'],
)]
#[OA\Response(
    response: 200,
    description: 'Current legal document versions',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'terms_of_service', properties: [
                    new OA\Property(property: 'version', type: 'string', example: '2026-09-16'),
                ], type: 'object'),
                new OA\Property(property: 'privacy_policy', properties: [
                    new OA\Property(property: 'version', type: 'string', example: '2026-09-16'),
                ], type: 'object'),
            ], type: 'object'),
        ],
    ),
)]
final class GetLegalDocumentsController
{
    public function __construct(
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return $this->apiResponse->success([
            'terms_of_service' => ['version' => LegalDocumentVersion::TERMS_OF_SERVICE],
            'privacy_policy' => ['version' => LegalDocumentVersion::PRIVACY_POLICY],
        ]);
    }
}
