<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Fixture;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Entity\Task;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\AttachmentId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class TaskFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Project $project */
        $project = $this->getReference(FixtureReference::PROJECT_JOHN_WEBSITE, Project::class);

        $entity = Task::create(
            id: TaskId::fromString(FixtureData::TASK_JOHN_WEBSITE_DESIGN_ID),
            project: $project,
            title: TaskTitle::fromString(FixtureData::TASK_JOHN_WEBSITE_DESIGN_TITLE),
            assigneeId: AssigneeId::fromString(FixtureData::USER_JANE_ID),
            attachmentId: AttachmentId::fromString(FixtureData::DOCUMENT_JOHN_INVOICE_ID),
        );
        $entity->pullDomainEvents();

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(FixtureReference::TASK_JOHN_WEBSITE_DESIGN, $entity);
    }

    public function getDependencies(): array
    {
        return [ProjectFixture::class];
    }
}
