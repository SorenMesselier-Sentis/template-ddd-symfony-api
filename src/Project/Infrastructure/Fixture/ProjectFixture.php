<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Fixture;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureFaker;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProjectFixture extends Fixture
{
    public function __construct(
        private readonly int $randomCount = 0,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $entity = Project::create(
            id: ProjectId::fromString(FixtureData::PROJECT_JOHN_WEBSITE_ID),
            ownerId: OwnerId::fromString(FixtureData::USER_JOHN_ID),
            name: ProjectName::fromString(FixtureData::PROJECT_JOHN_WEBSITE_NAME),
        );
        $entity->pullDomainEvents();

        $manager->persist($entity);
        $this->addReference(FixtureReference::PROJECT_JOHN_WEBSITE, $entity);

        $this->loadRandomProjects($manager);

        $manager->flush();
    }

    private function loadRandomProjects(ObjectManager $manager): void
    {
        if ($this->randomCount < 1) {
            return;
        }

        $faker = FixtureFaker::create();
        $owners = [FixtureData::USER_JOHN_ID, FixtureData::USER_JANE_ID, FixtureData::USER_BOB_ID];

        for ($i = 0; $i < $this->randomCount; ++$i) {
            $project = Project::create(
                id: ProjectId::random(),
                ownerId: OwnerId::fromString(FixtureFaker::randomElement($faker, $owners)),
                name: ProjectName::fromString(rtrim($faker->sentence(3), '.')),
            );

            if ($faker->boolean(15)) {
                $project->update(name: null, status: ProjectStatus::ARCHIVED);
            }

            $project->pullDomainEvents();
            $manager->persist($project);
        }
    }
}
