<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetUsers\GetUsersQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', methods: ['GET'])]
final class GetUsersController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('per_page', 20)));

        $result = $this->queryBus->ask(new GetUsersQuery($page, $perPage));

        return $this->apiResponse->paginated(
            data: $result->users,
            total: $result->total,
            page: $result->page,
            perPage: $result->perPage,
        );
    }
}
