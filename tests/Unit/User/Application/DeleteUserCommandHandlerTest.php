<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\DeleteUser\DeleteUserCommandHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteUserCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var EventBusInterface&MockObject */
    private EventBusInterface $eventBus;

    private LoggerInterface $logger;
    private DeleteUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new DeleteUserCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItDeletesAUser(): void
    {
        $user = UserMother::create();
        $command = new DeleteUserCommand(id: $user->id()->value());

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        ($this->handler)($command);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new DeleteUserCommand(id: UserMother::create()->id()->value());

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        ($this->handler)($command);
    }
}
