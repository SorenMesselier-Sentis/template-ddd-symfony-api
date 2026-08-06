<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\PutFeatureFlag\PutFeatureFlagCommand;
use App\User\Infrastructure\Http\Request\PutFeatureFlagRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/feature-flags/{key}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/feature-flags/{key}',
    operationId: 'putFeatureFlag',
    summary: 'Create or update a feature flag',
    description: 'Requires admin (`ROLE_ADMIN`). Full replace: creates the flag if it does not exist yet, otherwise '
        .'replaces its `enabled`/`description` state. Recorded in the audit trail.',
    tags: ['Feature flags'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'key', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'cursor_pagination'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['enabled'],
        properties: [
            new OA\Property(property: 'enabled', type: 'boolean', example: true),
            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Keyset pagination rollout.'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Flag created or updated (empty body)')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
final class PutFeatureFlagController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $key, PutFeatureFlagRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new PutFeatureFlagCommand(
            key: $key,
            enabled: $request->enabled(),
            description: $request->description(),
        ));

        return $this->apiResponse->noContent();
    }
}
