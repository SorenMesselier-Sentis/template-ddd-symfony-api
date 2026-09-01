<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResourceServer;

final class ResourceServerFactory
{
    public function __construct(
        private readonly AccessTokenRepositoryInterface $accessTokenRepository,
        private readonly string $publicKeyPath,
    ) {
    }

    public function create(): ResourceServer
    {
        return new ResourceServer(
            $this->accessTokenRepository,
            $this->publicKeyPath,
        );
    }
}
