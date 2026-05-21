<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Messaging;

use App\User\Application\Security\AuthorizedMessage;
use App\User\Application\Security\UserAuthorizer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AuthorizeMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly UserAuthorizer $authorizer,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof AuthorizedMessage) {
            $this->authorizer->assert($message->roleRequirement());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
