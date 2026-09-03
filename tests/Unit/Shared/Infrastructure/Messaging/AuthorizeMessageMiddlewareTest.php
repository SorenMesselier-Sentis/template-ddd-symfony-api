<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Exception\InsufficientPrivilegesException;
use App\Shared\Domain\Security\MessageAuthorizerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Messaging\AuthorizeMessageMiddleware;
use App\Shared\Infrastructure\Security\PrincipalRoleAuthorizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AuthorizeMessageMiddlewareTest extends UnitTestCase
{
    public function testItAuthorizesAuthorizedMessage(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('admin@example.com', null, ['ROLE_ADMIN']));

        $middleware = new AuthorizeMessageMiddleware(new PrincipalRoleAuthorizer($security));

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

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('user@example.com', null, ['ROLE_USER']));

        $middleware = new AuthorizeMessageMiddleware(new PrincipalRoleAuthorizer($security));
        $envelope = new Envelope(new CreateUserCommand(
            id: UserId::random()->value(),
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            password: 'secret1234',
        ));

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    public function testItSkipsNonAuthorizedMessages(): void
    {
        $authorizer = $this->createMock(MessageAuthorizerInterface::class);
        $authorizer->expects($this->never())->method('authorize');

        $middleware = new AuthorizeMessageMiddleware($authorizer);
        $envelope = new Envelope(new LoginUserCommand(
            email: Email::fromString('john@example.com'),
            password: 'secret',
        ));

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    private function terminatingStack(Envelope $envelope): StackInterface
    {
        return new class($envelope) implements StackInterface {
            public function __construct(private readonly Envelope $envelope)
            {
            }

            public function next(): MiddlewareInterface
            {
                return new class($this->envelope) implements MiddlewareInterface {
                    public function __construct(private readonly Envelope $envelope)
                    {
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
