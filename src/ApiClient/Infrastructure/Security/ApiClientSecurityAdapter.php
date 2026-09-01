<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The Symfony Security principal for an OAuth2 machine client — mirrors User BC's
 * SecurityUserAdapter, but for ApiClient instead of User. Scopes granted on the access token
 * become `SCOPE_<SCOPE_UPPER_SNAKE>` roles, on top of a blanket `ROLE_API_CLIENT`; commands/
 * queries that should accept a machine client declare it via
 * `RoleRequirement::any('ROLE_ADMIN', 'SCOPE_DOCUMENTS_WRITE')` — see docs/api-clients.md.
 */
final class ApiClientSecurityAdapter implements UserInterface
{
    /**
     * @param non-empty-string $clientId
     * @param list<string>     $scopes
     */
    public function __construct(
        private readonly string $clientId,
        private readonly array $scopes,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->clientId;
    }

    public function getRoles(): array
    {
        return ['ROLE_API_CLIENT', ...array_map(self::scopeToRole(...), $this->scopes)];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    private static function scopeToRole(string $scope): string
    {
        return 'SCOPE_'.strtoupper(str_replace(['-', ':', '.'], '_', $scope));
    }
}
