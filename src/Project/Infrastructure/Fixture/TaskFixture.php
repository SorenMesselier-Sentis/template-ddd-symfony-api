<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Fixture;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Entity\Task;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\AttachmentId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskStatus;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureFaker;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class TaskFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly int $randomCount = 0,
    ) {
    }

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
        $this->addReference(FixtureReference::TASK_JOHN_WEBSITE_DESIGN, $entity);

        $this->loadRandomTasks($manager, $project);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProjectFixture::class];
    }

    /**
     * Extra unreferenced tasks on the same demo project, purely to populate
     * the database with a realistic volume — e.g. for exercising task-list
     * pagination within a project. Assignee is one of the three named users
     * or unassigned; never attached to a document (keeps this independent
     * from `FIXTURES_RANDOM_DOCUMENT_COUNT`). Count is 0 unless
     * `FIXTURES_RANDOM_TASK_COUNT` is set (see README "Fixtures and test
     * data"); always 0 in the test env.
     */
    private function loadRandomTasks(ObjectManager $manager, Project $project): void
    {
        if ($this->randomCount < 1) {
            return;
        }

        $faker = FixtureFaker::create();
        $assignees = [FixtureData::USER_JOHN_ID, FixtureData::USER_JANE_ID, FixtureData::USER_BOB_ID];
        $statuses = [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::DONE];

        for ($i = 0; $i < $this->randomCount; ++$i) {
            $task = Task::create(
                id: TaskId::random(),
                project: $project,
                title: TaskTitle::fromString(rtrim($faker->sentence(5), '.')),
                assigneeId: $faker->boolean(70) ? AssigneeId::fromString(FixtureFaker::randomElement($faker, $assignees)) : null,
            );

            /** @var TaskStatus $status */
            $status = $faker->randomElement($statuses);

            if (TaskStatus::TODO !== $status) {
                $task->update(title: null, status: $status, assigneeId: null);
            }

            $task->pullDomainEvents();
            $manager->persist($task);
        }
    }
}
