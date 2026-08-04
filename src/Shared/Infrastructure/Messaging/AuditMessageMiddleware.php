<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Audit\AuditEntry;
use App\Shared\Domain\Audit\AuditTrailInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AuditMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuditTrailInterface $auditTrail,
        private readonly Security $security,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $isAuditable = $message instanceof AuditableMessage;
        $alreadyProcessing = $isAuditable && null !== $envelope->last(AuditProcessingStamp::class);

        if ($isAuditable && !$alreadyProcessing) {
            // The "sync" transport re-enters this bus's full middleware chain to
            // actually handle the command, so this middleware would otherwise run
            // twice per command. The stamp propagates through that re-entry and
            // lets the (chronologically first, deepest) nested pass skip recording,
            // leaving exactly one record — written after successful handling by
            // the outer pass once everything has unwound.
            $envelope = $envelope->with(new AuditProcessingStamp());
        }

        $result = $stack->next()->handle($envelope, $stack);

        if ($isAuditable && !$alreadyProcessing) {
            $this->auditTrail->record(AuditEntry::record(
                actorId: $this->security->getUser()?->getUserIdentifier(),
                action: $message->auditAction(),
                targetId: $message->auditTargetId(),
                context: $message->auditContext(),
            ));
        }

        return $result;
    }
}
