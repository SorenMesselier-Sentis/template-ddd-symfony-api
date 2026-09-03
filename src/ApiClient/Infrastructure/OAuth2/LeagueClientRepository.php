<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

final class LeagueClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $repository,
    ) {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $apiClient = $this->findActiveClient($clientIdentifier);

        if (null === $apiClient) {
            return null;
        }

        return new LeagueClientEntity(
            identifier: $apiClient->id()->value(),
            name: $apiClient->name(),
            allowedScopes: $apiClient->scopes(),
        );
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $apiClient = $this->findActiveClient($clientIdentifier);

        if (null === $apiClient || null === $clientSecret) {
            return false;
        }

        return $apiClient->secretHash()->verify($clientSecret);
    }

    private function findActiveClient(string $clientIdentifier): ?ApiClient
    {
        try {
            $id = ApiClientId::fromString($clientIdentifier);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $apiClient = $this->repository->findById($id);

        if (null === $apiClient || !$apiClient->isActive()) {
            return null;
        }

        return $apiClient;
    }
}
