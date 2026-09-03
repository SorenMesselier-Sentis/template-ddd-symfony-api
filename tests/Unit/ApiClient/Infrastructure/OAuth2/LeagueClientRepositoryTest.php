<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Infrastructure\OAuth2;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Infrastructure\OAuth2\LeagueClientEntity;
use App\ApiClient\Infrastructure\OAuth2\LeagueClientRepository;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;
use App\Tests\Unit\UnitTestCase;

final class LeagueClientRepositoryTest extends UnitTestCase
{
    public function testValidateClientAcceptsTheCorrectSecret(): void
    {
        $entity = ApiClientMother::create();
        $repository = $this->repositoryReturning($entity);

        $this->assertTrue($repository->validateClient($entity->id()->value(), 'test-secret', 'client_credentials'));
    }

    public function testValidateClientRejectsAWrongSecret(): void
    {
        $entity = ApiClientMother::create();
        $repository = $this->repositoryReturning($entity);

        $this->assertFalse($repository->validateClient($entity->id()->value(), 'wrong-secret', 'client_credentials'));
    }

    public function testValidateClientRejectsANullSecret(): void
    {
        $entity = ApiClientMother::create();
        $repository = $this->repositoryReturning($entity);

        $this->assertFalse($repository->validateClient($entity->id()->value(), null, 'client_credentials'));
    }

    public function testValidateClientRejectsARevokedClient(): void
    {
        $entity = ApiClientMother::create();
        $entity->revoke();
        $repository = $this->repositoryReturning($entity);

        $this->assertFalse($repository->validateClient($entity->id()->value(), 'test-secret', 'client_credentials'));
    }

    public function testValidateClientRejectsAMalformedClientId(): void
    {
        $apiClientRepository = $this->createStub(ApiClientRepositoryInterface::class);
        $repository = new LeagueClientRepository($apiClientRepository);

        $this->assertFalse($repository->validateClient('not-a-uuid', 'test-secret', 'client_credentials'));
    }

    public function testGetClientEntityExposesTheClientsGrantedScopes(): void
    {
        $entity = ApiClientMother::create(scopes: ['documents:write', 'documents:read']);
        $repository = $this->repositoryReturning($entity);

        $client = $repository->getClientEntity($entity->id()->value());

        $this->assertInstanceOf(LeagueClientEntity::class, $client);
        $this->assertSame($entity->id()->value(), $client->getIdentifier());
        $this->assertTrue($client->isConfidential());
        $this->assertSame(['documents:write', 'documents:read'], $client->allowedScopes());
    }

    public function testGetClientEntityReturnsNullForAnUnknownClient(): void
    {
        $apiClientRepository = $this->createStub(ApiClientRepositoryInterface::class);
        $apiClientRepository->method('findById')->willReturn(null);
        $repository = new LeagueClientRepository($apiClientRepository);

        $this->assertNull($repository->getClientEntity(ApiClientId::random()->value()));
    }

    private function repositoryReturning(ApiClient $entity): LeagueClientRepository
    {
        $apiClientRepository = $this->createStub(ApiClientRepositoryInterface::class);
        $apiClientRepository->method('findById')->willReturn($entity);

        return new LeagueClientRepository($apiClientRepository);
    }
}
