<?php

declare(strict_types=1);

namespace App\Shared\Domain\Audit;

use Symfony\Component\Uid\Uuid;

final readonly class AuditEntry
{
    /**
     * @param array<string, mixed> $context
     */
    private function __construct(
        public string $id,
        public ?string $actorId,
        public string $action,
        public string $targetId,
        public array $context,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function record(?string $actorId, string $action, string $targetId, array $context = []): self
    {
        return new self(
            id: Uuid::v4()->toRfc4122(),
            actorId: $actorId,
            action: $action,
            targetId: $targetId,
            context: $context,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
