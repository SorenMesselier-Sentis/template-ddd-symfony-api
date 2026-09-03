<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use App\ApiClient\Domain\Entity\IssuedAccessToken;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

final class LeagueAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private readonly IssuedAccessTokenRepositoryInterface $tokenRepository,
        private readonly ApiClientRepositoryInterface $apiClientRepository,
    ) {
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new LeagueAccessTokenEntity();
        $accessToken->setClient($clientEntity);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $apiClient = $this->apiClientRepository->findById(ApiClientId::fromString($accessTokenEntity->getClient()->getIdentifier()));

        if (null === $apiClient) {
            return;
        }

        $apiClient->recordUsage();
        $this->apiClientRepository->save($apiClient);

        $token = IssuedAccessToken::create(
            id: $accessTokenEntity->getIdentifier(),
            apiClient: $apiClient,
            scopes: array_values(array_map(static fn ($scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes())),
            expiresAt: $accessTokenEntity->getExpiryDateTime(),
        );

        $this->tokenRepository->save($token);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->tokenRepository->revoke($tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $token = $this->tokenRepository->findById($tokenId);

        return null === $token || $token->isRevoked();
    }
}
