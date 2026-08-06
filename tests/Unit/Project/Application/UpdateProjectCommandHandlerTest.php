<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\UpdateProject\UpdateProjectCommand;
use App\Project\Application\Command\UpdateProject\UpdateProjectCommandHandler;
use App\Project\Domain\Exception\InvalidProjectStatusException;
use App\Project\Domain\Exception\ProjectAlreadyExistsException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateProjectCommandHandlerTest extends UnitTestCase
{
    /** @var ProjectRepositoryInterface&MockObject */
    private ProjectRepositoryInterface $repository;

    private EventBusInterface $eventBus;
    private LoggerInterface $logger;
    private OwnerId $ownerId;
    private OwnerContextInterface $ownerContext;
    private UpdateProjectCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerId = OwnerId::random();
        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn($this->ownerId);
        $this->ownerContext->method('roles')->willReturn([]);

        $this->handler = new UpdateProjectCommandHandler(
            $this->repository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItUpdatesAProjectItOwns(): void
    {
        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new UpdateProjectCommand(id: $entity->id()->value(), name: 'New name');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->method('existsByOwnerIdAndName')->willReturn(false);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertSame('New name', $entity->name()->value());
    }

    public function testItThrowsWhenProjectNotFound(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new UpdateProjectCommand(id: ProjectMother::create()->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenCallerDoesNotOwnTheProject(): void
    {
        $this->expectException(ForbiddenException::class);

        $entity = ProjectMother::create(ownerId: OwnerId::random());
        $command = new UpdateProjectCommand(id: $entity->id()->value(), name: 'New name');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItThrowsOnInvalidStatusValue(): void
    {
        $this->expectException(InvalidProjectStatusException::class);

        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new UpdateProjectCommand(id: $entity->id()->value(), status: 'not-a-status');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);

        ($this->handler)($command);
    }

    public function testItThrowsWhenTryingToDeleteThroughStatus(): void
    {
        $this->expectException(InvalidProjectStatusException::class);

        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new UpdateProjectCommand(id: $entity->id()->value(), status: 'deleted');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);

        ($this->handler)($command);
    }

    public function testItThrowsWhenRenamingToAnExistingProjectName(): void
    {
        $this->expectException(ProjectAlreadyExistsException::class);

        $entity = ProjectMother::create(ownerId: $this->ownerId);
        $command = new UpdateProjectCommand(id: $entity->id()->value(), name: 'Taken name');

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->method('existsByOwnerIdAndName')->willReturn(true);

        ($this->handler)($command);
    }
}
