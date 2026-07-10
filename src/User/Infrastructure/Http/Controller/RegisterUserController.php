<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Infrastructure\Http\Request\RegisterUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/auth/register', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/register',
    operationId: 'postAuthRegister',
    summary: 'Register a new user',
    description: 'Creates a new user account. A verification email is sent.',
    tags: ['Authentication'],
    security: [],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['firstName', 'lastName', 'email', 'password'],
        properties: [
            new OA\Property(property: 'firstName', type: 'string', example: 'Alice'),
            new OA\Property(property: 'lastName', type: 'string', example: 'Wonder'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'alice@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret1234'),
        ],
    ),
)]
#[OA\Response(response: 201, description: 'User registered')]
#[OA\Response(response: 409, description: 'User already exists')]
final class RegisterUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new RegisterUserCommand(
            id: $id,
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
            password: $request->password(),
        ));

        return $this->apiResponse->created(['id' => $id]);
    }
}
