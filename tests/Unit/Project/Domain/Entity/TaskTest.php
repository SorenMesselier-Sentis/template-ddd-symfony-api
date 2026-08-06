<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Entity;

use App\Project\Domain\Event\TaskCreated;
use App\Project\Domain\Event\TaskDeleted;
use App\Project\Domain\Event\TaskReplaced;
use App\Project\Domain\Event\TaskUpdated;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\TaskStatus;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\Project\Domain\Mother\TaskMother;
use App\Tests\Unit\UnitTestCase;

final class TaskTest extends UnitTestCase
{
    public function testItCreatesATask(): void
    {
        $entity = TaskMother::create();
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskCreated::class, $events[0]);
        $this->assertSame(TaskStatus::TODO, $entity->status());
        $this->assertTrue($entity->isActive());
    }

    public function testItBelongsToItsProject(): void
    {
        $project = ProjectMother::create();
        $entity = TaskMother::create(project: $project);

        $this->assertTrue($project->id()->equals($entity->project()->id()));
    }

    public function testItRecordsAnUpdatedEventAndAppliesChanges(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $newTitle = TaskTitle::fromString('Renamed task');
        $assigneeId = AssigneeId::random();
        $entity->update($newTitle, TaskStatus::IN_PROGRESS, $assigneeId);
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskUpdated::class, $events[0]);
        $this->assertTrue($newTitle->equals($entity->title()));
        $this->assertSame(TaskStatus::IN_PROGRESS, $entity->status());
        $this->assertTrue($assigneeId->equals($entity->assigneeId()));
    }

    public function testUpdateIgnoresADeletedStatusTransition(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $entity->update(null, TaskStatus::DELETED, null);

        $this->assertSame(TaskStatus::TODO, $entity->status());
    }

    public function testDoneTaskIsNotActive(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $entity->update(null, TaskStatus::DONE, null);

        $this->assertFalse($entity->isActive());
    }

    public function testItRecordsAReplacedEvent(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $newTitle = TaskTitle::fromString('Fully replaced title');
        $entity->replace($newTitle, null);
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskReplaced::class, $events[0]);
        $this->assertTrue($newTitle->equals($entity->title()));
    }

    public function testItRecordsADeletedEvent(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $entity->delete();
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskDeleted::class, $events[0]);
        $this->assertSame(TaskStatus::DELETED, $entity->status());
        $this->assertFalse($entity->isActive());
    }

    public function testItClearsDomainEventsAfterPull(): void
    {
        $entity = TaskMother::create();
        $entity->pullDomainEvents();

        $this->assertEmpty($entity->pullDomainEvents());
    }
}
