<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Project\Application\Command\UpdateTask\UpdateTaskCommandHandler;
use App\Project\Domain\Exception\InvalidTaskStatusException;
use App\Project\Domain\Exception\TaskNotFoundException;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\Project\Domain\Mother\TaskMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateTaskCommandHandlerTest extends UnitTestCase
{
    /** @var TaskRepositoryInterface&MockObject */
    private TaskRepositoryInterface $repository;

    private EventBusInterface $eventBus;
    private LoggerInterface $logger;
    private OwnerId $ownerId;
    private OwnerContextInterface $ownerContext;
    private UpdateTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerId = OwnerId::random();
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn($this->ownerId);
        $this->ownerContext->method('roles')->willReturn([]);

        $this->handler = new UpdateTaskCommandHandler(
            $this->repository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItUpdatesATaskOfAProjectItOwns(): void
    {
        $project = ProjectMother::create(ownerId: $this->ownerId);
        $entity = TaskMother::create(project: $project);
        $command = new UpdateTaskCommand(id: $entity->id()->value(), status: 'in_progress');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);
    }

    public function testItThrowsWhenTaskNotFound(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $command = new UpdateTaskCommand(id: TaskMother::create()->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenCallerDoesNotOwnTheProject(): void
    {
        $this->expectException(ForbiddenException::class);

        $project = ProjectMother::create(ownerId: OwnerId::random());
        $entity = TaskMother::create(project: $project);
        $command = new UpdateTaskCommand(id: $entity->id()->value(), status: 'in_progress');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItThrowsOnInvalidStatusValue(): void
    {
        $this->expectException(InvalidTaskStatusException::class);

        $project = ProjectMother::create(ownerId: $this->ownerId);
        $entity = TaskMother::create(project: $project);
        $command = new UpdateTaskCommand(id: $entity->id()->value(), status: 'not-a-status');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);

        ($this->handler)($command);
    }
}
