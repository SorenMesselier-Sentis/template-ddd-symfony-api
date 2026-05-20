<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\HashedPasswordMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use App\User\Application\Command\LoginUser\LoginUserCommandHandler;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\AccessToken;
use App\User\Domain\ValueObject\RefreshToken as RefreshTokenValueObject;
use PHPUnit\Framework\MockObject\MockObject;

final class LoginUserCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    private TokenServiceInterface $tokenService;

    private LoggerInterface $logger;
    private LoginUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->refreshTokenRepository = $this->createStub(RefreshTokenRepositoryInterface::class);
        $this->tokenService = $this->createStub(TokenServiceInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new LoginUserCommandHandler(
            $this->repository,
            $this->refreshTokenRepository,
            $this->tokenService,
            $this->logger,
        );
    }

    public function testItLogsInAUser(): void
    {
        $email = EmailMother::create('login@example.com');
        $user = UserMother::create(
            email: $email,
            password: HashedPasswordMother::create('secret1234'),
        );
        $command = new LoginUserCommand(
            email: $email,
            password: 'secret1234',
        );

        /** @var RefreshTokenRepositoryInterface&MockObject $refreshTokenRepository */
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        /** @var TokenServiceInterface&MockObject $tokenService */
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $handler = new LoginUserCommandHandler(
            $this->repository,
            $refreshTokenRepository,
            $tokenService,
            $this->logger,
        );

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $tokenService
            ->expects($this->once())
            ->method('generateAccessToken')
            ->with($user)
            ->willReturn(new AccessToken('access-token', 3600));

        $tokenService
            ->expects($this->once())
            ->method('generateRefreshToken')
            ->with($user)
            ->willReturn(new RefreshTokenValueObject('refresh-token', 2592000));

        $refreshTokenRepository
            ->expects($this->once())
            ->method('save');

        $response = ($handler)($command);

        $this->assertEquals('access-token', $response->accessToken);
        $this->assertEquals(3600, $response->accessTokenExpiresIn);
        $this->assertEquals('refresh-token', $response->refreshToken);
        $this->assertEquals(2592000, $response->refreshTokenExpiresIn);
        $this->assertEquals('Bearer', $response->tokenType);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $email = Email::fromString('missing@example.com');
        $command = new LoginUserCommand(email: $email, password: 'secret1234');

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenPasswordIsInvalid(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $email = EmailMother::create('login@example.com');
        $user = UserMother::create(
            email: $email,
            password: HashedPasswordMother::create('secret1234'),
        );
        $command = new LoginUserCommand(email: $email, password: 'wrong-password');

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        ($this->handler)($command);
    }

    public function testItThrowsWhenAccountIsNotActive(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $email = EmailMother::create('deleted@example.com');
        $user = UserMother::create(
            email: $email,
            password: HashedPasswordMother::create('secret1234'),
        );
        $user->delete();
        $user->pullDomainEvents();

        $command = new LoginUserCommand(email: $email, password: 'secret1234');

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        ($this->handler)($command);
    }
}
