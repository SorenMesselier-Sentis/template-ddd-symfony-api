<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\CreateUserCommandHandler;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateUserCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var EventBusInterface&MockObject */
    private EventBusInterface $eventBus;

    private UserContextInterface $userContext;

    private LoggerInterface $logger;
    private CreateUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->userContext = $this->createStub(UserContextInterface::class);

        $this->userContext
            ->method('userId')
            ->willReturn(UserId::random());

        $this->handler = new CreateUserCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
            $this->userContext,
        );
    }

    public function testItCreatesAUser(): void
    {
        $command = new CreateUserCommand(
            id: UserId::random()->value(),
            firstName: 'John',
            lastName: 'Doe',
            email: 'john.doe@example.com',
            password: 'secret1234',
        );

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with($this->isInstanceOf(Email::class))
            ->willReturn(false);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        ($this->handler)($command);
    }

    public function testItThrowsWhenEmailAlreadyExists(): void
    {
        $this->expectException(UserAlreadyExistsException::class);

        $command = new CreateUserCommand(
            id: UserId::random()->value(),
            firstName: 'John',
            lastName: 'Doe',
            email: 'john.doe@example.com',
            password: 'secret1234',
        );

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->willReturn(true);

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        ($this->handler)($command);
    }
}
