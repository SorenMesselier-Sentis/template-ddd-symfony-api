<?php

declare(strict_types=1);

namespace App\Tests\Integration\Project\Infrastructure;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskStatus;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Project\Infrastructure\Persistence\Doctrine\Repository\DoctrineProjectRepository;
use App\Project\Infrastructure\Persistence\Doctrine\Repository\DoctrineTaskRepository;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\Project\Domain\Mother\TaskMother;

final class DoctrineTaskRepositoryTest extends IntegrationTestCase
{
    private DoctrineTaskRepository $repository;
    private DoctrineProjectRepository $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineTaskRepository($this->em);
        $this->projectRepository = new DoctrineProjectRepository($this->em);
    }

    public function testItSavesAndFindsATask(): void
    {
        $project = $this->persistedProject();
        $entity = TaskMother::create(project: $project);
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertTrue($entity->id()->equals($found->id()));
        $this->assertTrue($project->id()->equals($found->project()->id()));
    }

    public function testItReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findById(TaskId::random()));
    }

    public function testFindByIdReturnsNullForADeletedTask(): void
    {
        $project = $this->persistedProject();
        $entity = TaskMother::create(project: $project);
        $entity->delete();
        $this->repository->save($entity);

        $this->assertNull($this->repository->findById($entity->id()));
        $this->assertNotNull($this->repository->findByIdIncludingDeleted($entity->id()));
    }

    public function testExistsByProjectIdAndTitleIsScopedPerProject(): void
    {
        $project = $this->persistedProject();
        $otherProject = $this->persistedProject();
        $title = TaskTitle::fromString('Shared task title');

        $this->repository->save(TaskMother::create(project: $project, title: $title));

        $this->assertTrue($this->repository->existsByProjectIdAndTitle($project->id(), $title));
        $this->assertFalse($this->repository->existsByProjectIdAndTitle($otherProject->id(), $title));
    }

    public function testCountActiveByProjectIdExcludesDoneAndDeletedTasks(): void
    {
        $project = $this->persistedProject();

        $todo = TaskMother::create(project: $project, title: TaskTitle::fromString('Todo task'));
        $done = TaskMother::create(project: $project, title: TaskTitle::fromString('Done task'));
        $done->update(null, TaskStatus::DONE, null);
        $deleted = TaskMother::create(project: $project, title: TaskTitle::fromString('Deleted task'));
        $deleted->delete();

        foreach ([$todo, $done, $deleted] as $task) {
            $this->repository->save($task);
        }

        $this->assertSame(1, $this->repository->countActiveByProjectId($project->id()));
    }

    public function testFindByProjectIdAndFiltersOnlyReturnsThatProjectsActiveTasks(): void
    {
        $project = $this->persistedProject();
        $otherProject = $this->persistedProject();

        $owned = TaskMother::create(project: $project, title: TaskTitle::fromString('Owned task'));
        $deleted = TaskMother::create(project: $project, title: TaskTitle::fromString('Deleted task'));
        $deleted->delete();
        $foreign = TaskMother::create(project: $otherProject, title: TaskTitle::fromString('Foreign task'));

        foreach ([$owned, $deleted, $foreign] as $task) {
            $this->repository->save($task);
        }

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 20));

        $found = $this->repository->findByProjectIdAndFilters($project->id(), $filters);
        $foundIds = array_map(static fn ($t) => $t->id()->value(), $found);

        $this->assertContains($owned->id()->value(), $foundIds);
        $this->assertNotContains($deleted->id()->value(), $foundIds);
        $this->assertNotContains($foreign->id()->value(), $foundIds);
        $this->assertSame(\count($foundIds), $this->repository->countByProjectIdAndFilters($project->id(), $filters));
    }

    private function persistedProject(): Project
    {
        $project = ProjectMother::create();
        $this->projectRepository->save($project);

        return $project;
    }
}
