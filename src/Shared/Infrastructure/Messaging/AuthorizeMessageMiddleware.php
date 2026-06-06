<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Security\AuthorizedMessageContract;
use App\Shared\Domain\Security\MessageAuthorizerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AuthorizeMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageAuthorizerInterface $authorizer,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof AuthorizedMessageContract) {
            $this->authorizer->authorize($message);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
