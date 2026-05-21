<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Messaging;

use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use App\User\Application\Security\UserAuthorizer;
use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\Messaging\AuthorizeMessageMiddleware;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AuthorizeMessageMiddlewareTest extends UnitTestCase
{
    public function testItAuthorizesMessageImplementingAuthorizedMessage(): void
    {
        $userContext = $this->createStub(UserContextInterface::class);
        $userContext->method('roles')->willReturn([UserRole::ADMIN]);
        $userContext->method('isAuthenticated')->willReturn(true);
        $userContext->method('userId')->willReturn(UserId::random());

        $middleware = new AuthorizeMessageMiddleware(new UserAuthorizer($userContext));

        $envelope = new Envelope(new CreateUserCommand(
            id: UserId::random()->value(),
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            password: 'secret1234',
        ));

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertSame($envelope, $result);
    }

    public function testItThrowsWhenAuthorizationFails(): void
    {
        $this->expectException(InsufficientPrivilegesException::class);

        $userContext = $this->createStub(UserContextInterface::class);
        $userContext->method('roles')->willReturn([UserRole::USER]);
        $userContext->method('isAuthenticated')->willReturn(true);

        $middleware = new AuthorizeMessageMiddleware(new UserAuthorizer($userContext));

        $envelope = new Envelope(new CreateUserCommand(
            id: UserId::random()->value(),
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            password: 'secret1234',
        ));

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    public function testItSkipsMessagesWithoutAuthorizedMessageInterface(): void
    {
        $userContext = $this->createMock(UserContextInterface::class);
        $userContext->expects($this->never())->method('roles');

        $middleware = new AuthorizeMessageMiddleware(new UserAuthorizer($userContext));

        $envelope = new Envelope(new LoginUserCommand(
            email: Email::fromString('john@example.com'),
            password: 'secret',
        ));

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertSame($envelope, $result);
    }

    private function terminatingStack(Envelope $envelope): StackInterface
    {
        return new class($envelope) implements StackInterface {
            public function __construct(
                private readonly Envelope $envelope,
            ) {
            }

            public function next(): MiddlewareInterface
            {
                return new class($this->envelope) implements MiddlewareInterface {
                    public function __construct(
                        private readonly Envelope $envelope,
                    ) {
                    }

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $this->envelope;
                    }
                };
            }
        };
    }
}
