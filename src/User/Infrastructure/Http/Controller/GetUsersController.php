<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetUsers\GetUsersQuery;
use App\User\Application\Query\GetUsers\UsersResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', methods: ['GET'])]
#[OA\Get(path: '/api/v1/users', summary: 'List users', tags: ['Users'])]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1))]
#[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20))]
#[OA\Response(response: 200, description: 'List of users')]
final class GetUsersController
{
    Private const ALLOWED_FILTERS = [
        'email' => 'equal',
        'firstName' => 'equal',
        'lastName' => 'equal',
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $filters = FiltersBuilder::fromRequest($request, self::ALLOWED_FILTERS);

        $result = $this->queryBus->ask(new GetUsersQuery($filters));
        if (!$result instanceof UsersResponse) {
            throw new \RuntimeException(sprintf('Expected %s, %s given.', UsersResponse::class, get_debug_type($result)));
        }

        return $this->apiResponse->paginated(
            data: $result->users,
            total: $result->total,
            page: $result->page,
            perPage: $result->perPage,
        );
    }
}
