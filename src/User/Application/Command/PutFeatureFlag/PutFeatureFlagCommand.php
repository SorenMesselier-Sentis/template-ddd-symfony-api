<?php

declare(strict_types=1);

namespace App\User\Application\Command\PutFeatureFlag;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class PutFeatureFlagCommand implements Command, AuthorizedMessage, AuditableMessage
{
    public function __construct(
        public readonly string $key,
        public readonly bool $enabled,
        public readonly ?string $description,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }

    public function auditAction(): string
    {
        return 'feature_flag.updated';
    }

    public function auditTargetId(): string
    {
        return $this->key;
    }

    public function auditContext(): array
    {
        return ['enabled' => $this->enabled];
    }
}
