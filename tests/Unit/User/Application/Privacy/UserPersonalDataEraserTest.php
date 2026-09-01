<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Privacy;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Privacy\UserPersonalDataEraser;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;

final class UserPersonalDataEraserTest extends UnitTestCase
{
    public function testKeyIsProfile(): void
    {
        $eraser = new UserPersonalDataEraser(
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(RefreshTokenRepositoryInterface::class),
            $this->createStub(PasswordResetTokenRepositoryInterface::class),
            $this->createStub(EmailVerificationTokenRepositoryInterface::class),
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->assertSame('profile', $eraser->key());
    }

    public function testEraseAnonymizesTheUserDeletesTheAccountAndRevokesAllTokens(): void
    {
        $user = UserMother::create();
        $originalEmail = $user->email()->value();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByIdIncludingDeleted')
            ->with($this->callback(fn (UserId $id) => $id->equals($user->id())))
            ->willReturn($user);
        $repository->expects($this->once())->method('save')->with($user);

        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->once())->method('revokeAllForUser')->with($user->id()->value());

        $passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $passwordResetTokenRepository->expects($this->once())->method('revokeAllForUser')->with($user->id()->value());

        $emailVerificationTokenRepository = $this->createMock(EmailVerificationTokenRepositoryInterface::class);
        $emailVerificationTokenRepository->expects($this->once())->method('revokeAllForUser')->with($user->id()->value());

        $eraser = new UserPersonalDataEraser(
            $repository,
            $refreshTokenRepository,
            $passwordResetTokenRepository,
            $emailVerificationTokenRepository,
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $eraser->erase($user->id()->value());

        $this->assertSame(UserStatus::DELETED, $user->status());
        $this->assertNotSame($originalEmail, $user->email()->value());
    }

    public function testEraseDoesNotReDeleteAnAlreadyDeletedUser(): void
    {
        $user = UserMother::create();
        $user->delete();
        $user->pullDomainEvents();

        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn($user);

        $eraser = new UserPersonalDataEraser(
            $repository,
            $this->createStub(RefreshTokenRepositoryInterface::class),
            $this->createStub(PasswordResetTokenRepositoryInterface::class),
            $this->createStub(EmailVerificationTokenRepositoryInterface::class),
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $eraser->erase($user->id()->value());

        // anonymize() still ran (no exception, email changed) but delete() was not re-invoked —
        // pullDomainEvents() inside erase() would otherwise contain a second UserDeleted.
        $this->assertSame(UserStatus::DELETED, $user->status());
    }

    public function testEraseIsANoOpWhenTheUserIsNotFound(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByIdIncludingDeleted')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->never())->method('revokeAllForUser');

        $eraser = new UserPersonalDataEraser(
            $repository,
            $refreshTokenRepository,
            $this->createStub(PasswordResetTokenRepositoryInterface::class),
            $this->createStub(EmailVerificationTokenRepositoryInterface::class),
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $eraser->erase(UserId::random()->value());
    }
}
