<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

final class AuthorizationServerFactory
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly AccessTokenRepositoryInterface $accessTokenRepository,
        private readonly ScopeRepositoryInterface $scopeRepository,
        private readonly string $privateKeyPath,
        private readonly string $encryptionKey,
        private readonly int $accessTokenTtlSeconds,
    ) {
    }

    public function create(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->privateKeyPath,
            $this->encryptionKey,
        );

        $server->enableGrantType(
            new ClientCredentialsGrant(),
            new \DateInterval(sprintf('PT%dS', $this->accessTokenTtlSeconds)),
        );

        return $server;
    }
}
