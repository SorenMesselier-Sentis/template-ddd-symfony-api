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

    /**
     * Extra unreferenced projects on top of the named one above, purely to
     * populate the database with a realistic volume — e.g. for exercising
     * `GET /projects` pagination. Owned by the three named users only, so
     * every random project still belongs to a real, loggable-in account.
     * Count is 0 unless `FIXTURES_RANDOM_PROJECT_COUNT` is set (see README
     * "Fixtures and test data"); always 0 in the test env.
     */
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
