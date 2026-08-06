<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Entity;

use App\Project\Domain\Event\ProjectCreated;
use App\Project\Domain\Event\ProjectDeleted;
use App\Project\Domain\Event\ProjectReplaced;
use App\Project\Domain\Event\ProjectUpdated;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\UnitTestCase;

final class ProjectTest extends UnitTestCase
{
    public function testItCreatesAProject(): void
    {
        $entity = ProjectMother::create();
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectCreated::class, $events[0]);
        $this->assertSame(ProjectStatus::ACTIVE, $entity->status());
        $this->assertTrue($entity->isActive());
    }

    public function testItRecordsAnUpdatedEventAndRenames(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $newName = ProjectName::fromString('Renamed project');
        $entity->update($newName, null);
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectUpdated::class, $events[0]);
        $this->assertTrue($newName->equals($entity->name()));
    }

    public function testUpdateCanArchiveAndReactivate(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $entity->update(null, ProjectStatus::ARCHIVED);
        $this->assertSame(ProjectStatus::ARCHIVED, $entity->status());
        $this->assertFalse($entity->isActive());

        $entity->update(null, ProjectStatus::ACTIVE);
        $this->assertSame(ProjectStatus::ACTIVE, $entity->status());
        $this->assertTrue($entity->isActive());
    }

    public function testUpdateIgnoresADeletedStatusTransition(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $entity->update(null, ProjectStatus::DELETED);

        $this->assertSame(ProjectStatus::ACTIVE, $entity->status());
    }

    public function testItRecordsAReplacedEvent(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $newName = ProjectName::fromString('Fully replaced name');
        $entity->replace($newName);
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectReplaced::class, $events[0]);
        $this->assertTrue($newName->equals($entity->name()));
    }

    public function testItRecordsADeletedEvent(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $entity->delete();
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectDeleted::class, $events[0]);
        $this->assertSame(ProjectStatus::DELETED, $entity->status());
        $this->assertFalse($entity->isActive());
    }

    public function testItClearsDomainEventsAfterPull(): void
    {
        $entity = ProjectMother::create();
        $entity->pullDomainEvents();

        $this->assertEmpty($entity->pullDomainEvents());
    }
}
