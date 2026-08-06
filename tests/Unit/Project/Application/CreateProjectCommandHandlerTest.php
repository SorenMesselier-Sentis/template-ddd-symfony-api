<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Application;

use App\Project\Application\Command\CreateProject\CreateProjectCommand;
use App\Project\Application\Command\CreateProject\CreateProjectCommandHandler;
use App\Project\Domain\Exception\ProjectAlreadyExistsException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateProjectCommandHandlerTest extends UnitTestCase
{
    /** @var ProjectRepositoryInterface&MockObject */
    private ProjectRepositoryInterface $repository;

    /** @var EventBusInterface&MockObject */
    private EventBusInterface $eventBus;

    private OwnerContextInterface $ownerContext;
    private LoggerInterface $logger;
    private CreateProjectCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->ownerContext = $this->createStub(OwnerContextInterface::class);
        $this->ownerContext->method('ownerId')->willReturn(OwnerId::random());

        $this->handler = new CreateProjectCommandHandler(
            $this->repository,
            $this->ownerContext,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItCreatesAProject(): void
    {
        $command = new CreateProjectCommand(id: ProjectId::random()->value(), name: 'Website Redesign');

        $this->repository->method('existsByOwnerIdAndName')->willReturn(false);
        $this->repository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('publish');

        ($this->handler)($command);
    }

    public function testItThrowsWhenAProjectWithTheSameNameAlreadyExistsForTheOwner(): void
    {
        $this->expectException(ProjectAlreadyExistsException::class);

        $command = new CreateProjectCommand(id: ProjectId::random()->value(), name: 'Website Redesign');

        $this->repository->method('existsByOwnerIdAndName')->willReturn(true);
        $this->repository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        ($this->handler)($command);
    }
}
