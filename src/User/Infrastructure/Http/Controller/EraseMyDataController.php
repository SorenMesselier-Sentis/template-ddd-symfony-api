<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\EraseMyData\EraseMyDataCommand;
use App\User\Domain\Security\UserContextInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/users/me',
    operationId: 'eraseMyData',
    summary: 'Erase all personal data held about the current user (GDPR right to erasure)',
    description: 'Anonymizes the profile (name, email, password), revokes every refresh/password-reset/'
        .'email-verification token, and erases personal data held by every other bounded context '
        .'(e.g. permanently deletes owned documents, including their storage objects). Irreversible.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 204, description: 'Personal data erased')]
#[OA\Response(response: 401, description: 'Unauthenticated')]
final class EraseMyDataController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly UserContextInterface $userContext,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $this->commandBus->dispatch(new EraseMyDataCommand($this->userContext->userId()->value()));

        return $this->apiResponse->noContent();
    }
}
