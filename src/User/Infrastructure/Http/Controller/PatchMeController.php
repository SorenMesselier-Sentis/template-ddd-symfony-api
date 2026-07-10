<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Exception\EmptyPatchException;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\UpdateMe\UpdateMeCommand;
use App\User\Infrastructure\Http\Request\PatchMeRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/v1/users/me',
    operationId: 'patchMe',
    summary: 'Update current user profile',
    description: 'Updates the authenticated user profile. Changing email requires re-verification.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'firstName', type: 'string'),
            new OA\Property(property: 'lastName', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Profile updated')]
final class PatchMeController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(PatchMeRequest $request): JsonResponse
    {
        if ($request->isEmpty()) {
            throw new EmptyPatchException('At least one field must be provided.');
        }

        $this->commandBus->dispatch(new UpdateMeCommand(
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
        ));

        return $this->apiResponse->noContent();
    }
}
