<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Infrastructure\Http\Request\CreateUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Users')]
#[Route('/users', methods: ['POST'])]
final class CreateUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {}

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Create a user',
        description: 'Creates a new user account.',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['firstName', 'lastName', 'email', 'password'],
            properties: [
                new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Response(response: 409, description: 'Email already registered')]
    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new CreateUserCommand(
            id: $id,
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
            password: $request->password(),
        ));

        return $this->apiResponse->created(['id' => $id]);
    }
}
