<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\ChangePassword\ChangePasswordCommand;
use App\User\Infrastructure\Http\Request\ChangePasswordRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/password', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/users/me/password',
    operationId: 'postMePassword',
    summary: 'Change password',
    description: 'Changes the authenticated user password. Requires the current password.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['currentPassword', 'newPassword'],
        properties: [
            new OA\Property(property: 'currentPassword', type: 'string', format: 'password'),
            new OA\Property(property: 'newPassword', type: 'string', format: 'password'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Password changed')]
#[OA\Response(response: 401, description: 'Invalid current password')]
final class ChangePasswordController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ChangePasswordCommand(
            currentPassword: $request->currentPassword(),
            newPassword: $request->newPassword(),
        ));

        return $this->apiResponse->noContent();
    }
}
