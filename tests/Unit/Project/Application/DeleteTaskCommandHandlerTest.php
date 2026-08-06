<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\DeleteTask\DeleteTaskCommand;
use App\Project\Application\Command\DeleteTask\DeleteTaskCommandHandler;
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

final class DeleteTaskCommandHandlerTest extends UnitTestCase
{
    /** @var TaskRepositoryInterface&MockObject */
    private TaskRepositoryInterface $repository;

    private EventBusInterface $eventBus;
    private LoggerInterface $logger;
    private OwnerId $ownerId;
    private OwnerContextInterface $ownerContext;
    private DeleteTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerId = OwnerId::random();
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn($this->ownerId);
        $this->ownerContext->method('roles')->willReturn([]);

        $this->handler = new DeleteTaskCommandHandler(
            $this->repository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItDeletesATaskOfAProjectItOwns(): void
    {
        $project = ProjectMother::create(ownerId: $this->ownerId);
        $entity = TaskMother::create(project: $project);
        $command = new DeleteTaskCommand(id: $entity->id()->value());

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $handler = new DeleteTaskCommandHandler(
            $this->repository,
            $this->ownerContext,
            $eventBus,
            $this->logger,
        );

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');
        $eventBus->expects($this->once())->method('publish');

        ($handler)($command);
    }

    public function testItThrowsWhenTaskNotFound(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $command = new DeleteTaskCommand(id: TaskMother::create()->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenCallerDoesNotOwnTheProject(): void
    {
        $this->expectException(ForbiddenException::class);

        $project = ProjectMother::create(ownerId: OwnerId::random());
        $entity = TaskMother::create(project: $project);
        $command = new DeleteTaskCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
