<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\User\Infrastructure\Security\SecurityUserAdapter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/realtime-token', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/users/me/realtime-token',
    operationId: 'getRealtimeSubscriberToken',
    summary: 'Mint a Mercure subscriber authorization cookie for the current user',
    description: 'Sets a `mercureAuthorization` cookie (scoped to `/.well-known/mercure`) that authorizes the '
        .'caller to subscribe to their own private real-time topics (e.g. `/users/{id}/notifications`). Call this '
        .'once before opening an EventSource connection to the Mercure hub.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 204, description: 'Subscriber authorization cookie set (empty body)')]
#[OA\Response(response: 401, description: 'Unauthenticated')]
final class GetRealtimeSubscriberTokenController
{
    public function __construct(
        private readonly Security $security,
        private readonly Authorization $authorization,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $adapter = $this->security->getUser();

        if (!$adapter instanceof SecurityUserAdapter) {
            throw new \LogicException(sprintf('Expected an authenticated %s.', SecurityUserAdapter::class));
        }

        $topics = [sprintf('/users/%s/notifications', $adapter->getUser()->id()->value())];

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $response->headers->setCookie($this->authorization->createCookie($request, $topics));

        return $response;
    }
}
