<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Domain;

use App\ApiClient\Domain\Event\ApiClientCreated;
use App\ApiClient\Domain\Event\ApiClientDeleted;
use App\ApiClient\Domain\Event\ApiClientRevoked;
use App\ApiClient\Domain\Event\ApiClientSecretRotated;
use App\ApiClient\Domain\ValueObject\ApiClientStatus;
use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;
use App\Tests\Unit\UnitTestCase;

final class ApiClientTest extends UnitTestCase
{
    public function testCreateRecordsAnApiClientCreatedEventAndDefaultsToActive(): void
    {
        $entity = ApiClientMother::create();

        $this->assertSame(ApiClientStatus::ACTIVE, $entity->status());
        $this->assertTrue($entity->isActive());
        $this->assertNull($entity->lastUsedAt());

        $events = $entity->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ApiClientCreated::class, $events[0]);
        $this->assertSame([], $entity->pullDomainEvents());
    }

    public function testRevokeTransitionsStatusAndRecordsEvent(): void
    {
        $entity = ApiClientMother::create();
        $entity->pullDomainEvents();

        $entity->revoke();

        $this->assertSame(ApiClientStatus::REVOKED, $entity->status());
        $this->assertFalse($entity->isActive());
        $this->assertInstanceOf(ApiClientRevoked::class, $entity->pullDomainEvents()[0]);
    }

    public function testDeleteTransitionsStatusAndRecordsEvent(): void
    {
        $entity = ApiClientMother::create();
        $entity->pullDomainEvents();

        $entity->delete();

        $this->assertSame(ApiClientStatus::DELETED, $entity->status());
        $this->assertFalse($entity->isActive());
        $this->assertInstanceOf(ApiClientDeleted::class, $entity->pullDomainEvents()[0]);
    }

    public function testRotateSecretReplacesTheHashAndRecordsEvent(): void
    {
        $entity = ApiClientMother::create();
        $entity->pullDomainEvents();

        $newHash = HashedClientSecret::fromPlainSecret('a-new-secret');
        $entity->rotateSecret($newHash);

        $this->assertTrue($entity->secretHash()->verify('a-new-secret'));
        $this->assertInstanceOf(ApiClientSecretRotated::class, $entity->pullDomainEvents()[0]);
    }

    public function testRecordUsageSetsLastUsedAt(): void
    {
        $entity = ApiClientMother::create();

        $entity->recordUsage();

        $this->assertNotNull($entity->lastUsedAt());
    }
}
