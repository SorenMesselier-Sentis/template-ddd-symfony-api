<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\DeleteProject\DeleteProjectCommand;
use App\Project\Application\Command\DeleteProject\DeleteProjectCommandHandler;
use App\Project\Domain\Exception\ProjectHasActiveTasksException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteProjectCommandHandlerTest extends UnitTestCase
{
    /** @var ProjectRepositoryInterface&MockObject */
    private ProjectRepositoryInterface $repository;

    private TaskRepositoryInterface $taskRepository;

    private EventBusInterface $eventBus;
    private LoggerInterface $logger;
    private OwnerId $ownerId;
    private OwnerContextInterface $ownerContext;
    private DeleteProjectCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectRepositoryInterface::class);
        $this->taskRepository = $this->createStub(TaskRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerId = OwnerId::random();
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn($this->ownerId);
        $this->ownerContext->method('roles')->willReturn([]);

        $this->handler = new DeleteProjectCommandHandler(
            $this->repository,
            $this->taskRepository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItDeletesAProjectWithNoActiveTasks(): void
    {
        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new DeleteProjectCommand(id: $entity->id()->value());

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $handler = new DeleteProjectCommandHandler(
            $this->repository,
            $this->taskRepository,
            $this->ownerContext,
            $eventBus,
            $this->logger,
        );

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->taskRepository->method('countActiveByProjectId')->willReturn(0);
        $this->repository->expects($this->once())->method('save');
        $eventBus->expects($this->once())->method('publish');

        ($handler)($command);
    }

    public function testItThrowsWhenProjectNotFound(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new DeleteProjectCommand(id: ProjectMother::create()->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenCallerDoesNotOwnTheProject(): void
    {
        $this->expectException(ForbiddenException::class);

        $entity = ProjectMother::create(ownerId: OwnerId::random());
        $command = new DeleteProjectCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItThrowsWhenTheProjectStillHasActiveTasks(): void
    {
        $this->expectException(ProjectHasActiveTasksException::class);

        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new DeleteProjectCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->taskRepository->method('countActiveByProjectId')->willReturn(2);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
