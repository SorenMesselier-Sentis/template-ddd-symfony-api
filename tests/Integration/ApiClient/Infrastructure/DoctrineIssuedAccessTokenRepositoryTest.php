<?php

declare(strict_types=1);

namespace App\Tests\Integration\ApiClient\Infrastructure;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\Entity\IssuedAccessToken;
use App\ApiClient\Infrastructure\Persistence\Doctrine\Repository\DoctrineApiClientRepository;
use App\ApiClient\Infrastructure\Persistence\Doctrine\Repository\DoctrineIssuedAccessTokenRepository;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;

final class DoctrineIssuedAccessTokenRepositoryTest extends IntegrationTestCase
{
    private DoctrineIssuedAccessTokenRepository $repository;
    private DoctrineApiClientRepository $apiClientRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineIssuedAccessTokenRepository($this->em);
        $this->apiClientRepository = new DoctrineApiClientRepository($this->em);
    }

    public function testItSavesAndFindsAToken(): void
    {
        $apiClient = $this->persistedApiClient();
        $token = $this->tokenFor($apiClient);
        $this->repository->save($token);

        $found = $this->repository->findById($token->id());

        $this->assertNotNull($found);
        $this->assertSame($token->id(), $found->id());
        $this->assertTrue($apiClient->id()->equals($found->apiClient()->id()));
        $this->assertFalse($found->isRevoked());
    }

    public function testRevokeMarksTheTokenRevoked(): void
    {
        $apiClient = $this->persistedApiClient();
        $token = $this->tokenFor($apiClient);
        $this->repository->save($token);

        $this->repository->revoke($token->id());
        $this->em->clear();

        $found = $this->repository->findById($token->id());
        $this->assertNotNull($found);
        $this->assertTrue($found->isRevoked());
    }

    public function testRevokeAllForClientRevokesOnlyThatClientsTokens(): void
    {
        $apiClient = $this->persistedApiClient();
        $otherClient = $this->persistedApiClient();

        $token = $this->tokenFor($apiClient);
        $otherToken = $this->tokenFor($otherClient);
        $this->repository->save($token);
        $this->repository->save($otherToken);

        $this->repository->revokeAllForClient($apiClient->id()->value());
        $this->em->clear();

        $this->assertTrue($this->repository->findById($token->id())?->isRevoked());
        $this->assertFalse($this->repository->findById($otherToken->id())?->isRevoked());
    }

    public function testDeleteExpiredRemovesOnlyExpiredTokens(): void
    {
        $apiClient = $this->persistedApiClient();

        $expired = $this->tokenFor($apiClient, expiresAt: new \DateTimeImmutable('-1 hour'));
        $valid = $this->tokenFor($apiClient, expiresAt: new \DateTimeImmutable('+1 hour'));
        $this->repository->save($expired);
        $this->repository->save($valid);

        $deleted = $this->repository->deleteExpired(new \DateTimeImmutable('now'));
        $this->em->clear();

        $this->assertSame(1, $deleted);
        $this->assertNull($this->repository->findById($expired->id()));
        $this->assertNotNull($this->repository->findById($valid->id()));
    }

    private function persistedApiClient(): ApiClient
    {
        $apiClient = ApiClientMother::create(name: 'Client '.uniqid());
        $this->apiClientRepository->save($apiClient);

        return $apiClient;
    }

    private function tokenFor(ApiClient $apiClient, ?\DateTimeImmutable $expiresAt = null): IssuedAccessToken
    {
        return IssuedAccessToken::create(
            id: bin2hex(random_bytes(20)),
            apiClient: $apiClient,
            scopes: ['documents:write'],
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+1 hour'),
        );
    }
}
