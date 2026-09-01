<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\OAuth2;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

final class LeagueScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;

    public function __construct(string $identifier)
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('Scope identifier must not be empty.');
        }

        $this->identifier = $identifier;
    }
}
