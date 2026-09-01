<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * Lightweight league/oauth2-server DTO for our ApiClient domain entity — never persisted
 * itself, rebuilt on every lookup by LeagueClientRepository. All clients here are
 * confidential (a secret is always required — see CreateApiClientCommandHandler); there is no
 * redirect URI since only the client_credentials grant is supported.
 */
final class LeagueClientEntity implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    /**
     * @param list<string> $allowedScopes
     */
    public function __construct(
        string $identifier,
        string $name,
        private readonly array $allowedScopes,
    ) {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('Client identifier must not be empty.');
        }

        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = '';
        $this->isConfidential = true;
    }

    /**
     * @return list<string>
     */
    public function allowedScopes(): array
    {
        return $this->allowedScopes;
    }
}
