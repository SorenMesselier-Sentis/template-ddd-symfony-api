<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Fixture;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ApiClientFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $entity = ApiClient::create(
            id: ApiClientId::fromString(FixtureData::API_CLIENT_TEST_ID),
            name: FixtureData::API_CLIENT_TEST_NAME,
            secretHash: HashedClientSecret::fromPlainSecret(FixtureData::API_CLIENT_TEST_SECRET),
            scopes: [FixtureData::API_CLIENT_TEST_SCOPE],
        );
        $entity->pullDomainEvents();

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(FixtureReference::API_CLIENT_TEST, $entity);
    }
}
