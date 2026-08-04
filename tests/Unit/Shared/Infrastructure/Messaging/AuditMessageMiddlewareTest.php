<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Audit\AuditEntry;
use App\Shared\Domain\Audit\AuditTrailInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Messaging\AuditMessageMiddleware;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use App\User\Application\Command\UpdateUserRoles\UpdateUserRolesCommand;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuditMessageMiddlewareTest extends UnitTestCase
{
    public function testItRecordsAuditableMessageAfterSuccessfulHandling(): void
    {
        $admin = $this->createStub(UserInterface::class);
        $admin->method('getUserIdentifier')->willReturn('admin@example.com');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($admin);

        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $recorded = null;
        $auditTrail->expects($this->once())
            ->method('record')
            ->willReturnCallback(function (AuditEntry $entry) use (&$recorded): void {
                $recorded = $entry;
            });

        $middleware = new AuditMessageMiddleware($auditTrail, $security);
        $envelope = new Envelope(new UpdateUserRolesCommand(id: 'user-1', roles: ['ROLE_ADMIN']));

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertSame($envelope, $result);
        $this->assertNotNull($recorded);
        $this->assertSame('admin@example.com', $recorded->actorId);
        $this->assertSame('user.roles_updated', $recorded->action);
        $this->assertSame('user-1', $recorded->targetId);
        $this->assertSame(['roles' => ['ROLE_ADMIN']], $recorded->context);
    }

    public function testActorIsNullWhenNoAuthenticatedUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $recorded = null;
        $auditTrail->expects($this->once())
            ->method('record')
            ->willReturnCallback(function (AuditEntry $entry) use (&$recorded): void {
                $recorded = $entry;
            });

        $middleware = new AuditMessageMiddleware($auditTrail, $security);
        $envelope = new Envelope(new LoginUserCommand(email: Email::fromString('john@example.com'), password: 'secret'));

        $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertNotNull($recorded);
        $this->assertNull($recorded->actorId);
        $this->assertSame('user.logged_in', $recorded->action);
        $this->assertSame('john@example.com', $recorded->targetId);
    }

    public function testItSkipsNonAuditableMessages(): void
    {
        $security = $this->createStub(Security::class);
        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $auditTrail->expects($this->never())->method('record');

        $middleware = new AuditMessageMiddleware($auditTrail, $security);
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    public function testItDoesNotRecordWhenHandlingFails(): void
    {
        $security = $this->createStub(Security::class);
        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $auditTrail->expects($this->never())->method('record');

        $middleware = new AuditMessageMiddleware($auditTrail, $security);
        $envelope = new Envelope(new DeleteUserCommand(id: 'user-1'));

        $this->expectException(\RuntimeException::class);
        $middleware->handle($envelope, $this->failingStack());
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

    private function failingStack(): StackInterface
    {
        return new class implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class implements MiddlewareInterface {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        throw new \RuntimeException('handler failed');
                    }
                };
            }
        };
    }
}
