<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\CreateTask\CreateTaskCommand;
use App\Project\Application\Command\CreateTask\CreateTaskCommandHandler;
use App\Project\Domain\Exception\ProjectNotActiveException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Exception\TaskAlreadyExistsException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Project\Domain\ValueObject\TaskId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTaskCommandHandlerTest extends UnitTestCase
{
    private TaskRepositoryInterface $repository;
    private ProjectRepositoryInterface $projectRepository;
    private EventBusInterface $eventBus;
    private LoggerInterface $logger;
    private OwnerId $ownerId;
    private OwnerContextInterface $ownerContext;
    private CreateTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(TaskRepositoryInterface::class);
        $this->projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerId = OwnerId::random();
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn($this->ownerId);
        $this->ownerContext->method('roles')->willReturn([]);

        $this->handler = new CreateTaskCommandHandler(
            $this->repository,
            $this->projectRepository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItCreatesATask(): void
    {
        $project = ProjectMother::create(ownerId: $this->ownerId);
        $command = new CreateTaskCommand(id: TaskId::random()->value(), projectId: $project->id()->value(), title: 'Design the homepage');

        /** @var TaskRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository->method('existsByProjectIdAndTitle')->willReturn(false);
        $repository->expects($this->once())->method('save');

        $projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $projectRepository->method('findById')->willReturn($project);

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->once())->method('publish');

        $handler = new CreateTaskCommandHandler($repository, $projectRepository, $this->ownerContext, $eventBus, $this->logger);

        ($handler)($command);
    }

    public function testItThrowsWhenProjectNotFound(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new CreateTaskCommand(id: TaskId::random()->value(), projectId: ProjectMother::create()->id()->value(), title: 'Design the homepage');

        $this->projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $this->projectRepository->method('findById')->willReturn(null);

        $handler = new CreateTaskCommandHandler($this->repository, $this->projectRepository, $this->ownerContext, $this->eventBus, $this->logger);

        ($handler)($command);
    }

    public function testItThrowsWhenCallerDoesNotOwnTheProject(): void
    {
        $this->expectException(ForbiddenException::class);

        $project = ProjectMother::create(ownerId: OwnerId::random());
        $command = new CreateTaskCommand(id: TaskId::random()->value(), projectId: $project->id()->value(), title: 'Design the homepage');

        $this->projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $this->projectRepository->method('findById')->willReturn($project);

        $handler = new CreateTaskCommandHandler($this->repository, $this->projectRepository, $this->ownerContext, $this->eventBus, $this->logger);

        ($handler)($command);
    }

    public function testItThrowsWhenTheProjectIsNotActive(): void
    {
        $this->expectException(ProjectNotActiveException::class);

        $project = ProjectMother::create(ownerId: $this->ownerId);
        $project->pullDomainEvents();
        $project->update(null, ProjectStatus::ARCHIVED);
        $command = new CreateTaskCommand(id: TaskId::random()->value(), projectId: $project->id()->value(), title: 'Design the homepage');

        $this->projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $this->projectRepository->method('findById')->willReturn($project);

        $handler = new CreateTaskCommandHandler($this->repository, $this->projectRepository, $this->ownerContext, $this->eventBus, $this->logger);

        ($handler)($command);
    }

    public function testItThrowsWhenATaskWithTheSameTitleAlreadyExistsInTheProject(): void
    {
        $this->expectException(TaskAlreadyExistsException::class);

        $project = ProjectMother::create(ownerId: $this->ownerId);
        $command = new CreateTaskCommand(id: TaskId::random()->value(), projectId: $project->id()->value(), title: 'Design the homepage');

        $this->projectRepository = $this->createStub(ProjectRepositoryInterface::class);
        $this->projectRepository->method('findById')->willReturn($project);

        $this->repository = $this->createStub(TaskRepositoryInterface::class);
        $this->repository->method('existsByProjectIdAndTitle')->willReturn(true);

        $handler = new CreateTaskCommandHandler($this->repository, $this->projectRepository, $this->ownerContext, $this->eventBus, $this->logger);

        ($handler)($command);
    }
}
