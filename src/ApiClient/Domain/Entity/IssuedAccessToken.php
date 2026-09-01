<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Entity;

/**
 * Revocation-tracking record for an OAuth2 access token minted by league/oauth2-server —
 * mirrors {@see \App\User\Domain\Entity\RefreshToken}. `id` is league's own generated token
 * identifier (an opaque random string, not a UUID — see LeagueAccessTokenRepository), which is
 * why it's a plain string rather than a dedicated Uuid value object.
 */
final class IssuedAccessToken
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly string $id,
        private readonly ApiClient $apiClient,
        private readonly array $scopes,
        private readonly \DateTimeImmutable $expiresAt,
        private bool $revoked,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param list<string> $scopes
     */
    public static function create(
        string $id,
        ApiClient $apiClient,
        array $scopes,
        \DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: $id,
            apiClient: $apiClient,
            scopes: $scopes,
            expiresAt: $expiresAt,
            revoked: false,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function apiClient(): ApiClient
    {
        return $this->apiClient;
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
