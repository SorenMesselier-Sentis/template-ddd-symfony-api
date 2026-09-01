<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * No global scope registry: any string is a valid scope identifier. The real access-control
 * boundary is finalizeScopes() below, which never grants more than what's on the client's own
 * `scopes` record (see ApiClient::scopes() / LeagueClientEntity::allowedScopes()).
 */
final class LeagueScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        if ('' === $identifier) {
            return null;
        }

        return new LeagueScopeEntity($identifier);
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        $allowedScopes = $clientEntity instanceof LeagueClientEntity ? $clientEntity->allowedScopes() : [];

        if ([] === $scopes) {
            return array_map(static fn (string $scope) => new LeagueScopeEntity($scope), $allowedScopes);
        }

        return array_values(array_filter(
            $scopes,
            static fn (ScopeEntityInterface $scope) => \in_array($scope->getIdentifier(), $allowedScopes, true),
        ));
    }
}
