<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Marks an envelope as already seen by AuditMessageMiddleware.
 *
 * The "sync" transport re-enters command.bus's full middleware chain (send
 * the envelope, then dispatch it again to actually handle it), so any
 * middleware positioned before the implicit send_message/handle_message pair
 * runs twice per command unless it deduplicates via a stamp — this one
 * carries across that re-entry since stamps propagate through senders.
 */
final class AuditProcessingStamp implements StampInterface
{
}
