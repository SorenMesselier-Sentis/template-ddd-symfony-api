<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Privacy\PersonalDataEraserInterface;
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

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
    }

    public function testItInvokesEveryGdprEraserForTheDeletedUser(): void
    {
        $user = UserMother::create();
        $command = new DeleteUserCommand(id: $user->id()->value());

        /** @var PersonalDataEraserInterface&MockObject $eraserA */
        $eraserA = $this->createMock(PersonalDataEraserInterface::class);
        /** @var PersonalDataEraserInterface&MockObject $eraserB */
        $eraserB = $this->createMock(PersonalDataEraserInterface::class);

        $eraserA->expects($this->once())->method('erase')->with($user->id()->value());
        $eraserB->expects($this->once())->method('erase')->with($user->id()->value());

        $handler = new DeleteUserCommandHandler($this->repository, [$eraserA, $eraserB], $this->logger);

        $this->repository->expects($this->once())->method('findById')->willReturn($user);

        ($handler)($command);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new DeleteUserCommand(id: UserMother::create()->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        $handler = new DeleteUserCommandHandler($this->repository, [], $this->logger);

        ($handler)($command);
    }
}
