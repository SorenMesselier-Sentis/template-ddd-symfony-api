<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Fixture;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProjectFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $entity = Project::create(
            id: ProjectId::fromString(FixtureData::PROJECT_JOHN_WEBSITE_ID),
            ownerId: OwnerId::fromString(FixtureData::USER_JOHN_ID),
            name: ProjectName::fromString(FixtureData::PROJECT_JOHN_WEBSITE_NAME),
        );
        $entity->pullDomainEvents();

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(FixtureReference::PROJECT_JOHN_WEBSITE, $entity);
    }
}
