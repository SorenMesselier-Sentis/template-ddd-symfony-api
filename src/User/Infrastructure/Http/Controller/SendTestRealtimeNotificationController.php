<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\SendTestRealtimeNotification\SendTestRealtimeNotificationCommand;
use App\User\Infrastructure\Http\Request\SendTestRealtimeNotificationRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/realtime-test', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/users/me/realtime-test',
    operationId: 'postMeRealtimeTest',
    summary: 'Send a test real-time notification to yourself',
    description: 'Example endpoint demonstrating the Mercure real-time port: publishes an in-app notification to '
        .'the caller\'s own private topic (`/users/{id}/notifications`), which any subscribed EventSource receives '
        .'live. Call `GET /users/me/realtime-token` first to authorize the subscription.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: false,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Hello from Mercure!'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Notification published')]
#[OA\Response(response: 401, description: 'Unauthenticated')]
final class SendTestRealtimeNotificationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(SendTestRealtimeNotificationRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new SendTestRealtimeNotificationCommand(
            message: $request->message(),
        ));

        return $this->apiResponse->noContent();
    }
}
