<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClient;

use App\ApiClient\Domain\Exception\ApiClientNotFoundException;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetApiClientQueryHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetApiClientQuery $query): ApiClientResponse
    {
        $entity = $this->repository->findById(ApiClientId::fromString($query->id));

        if (null === $entity) {
            throw ApiClientNotFoundException::withId($query->id);
        }

        return new ApiClientResponse($entity);
    }
}
