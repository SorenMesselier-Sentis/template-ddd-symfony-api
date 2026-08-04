<?php

declare(strict_types=1);

namespace App\Shared\Domain\Audit;

interface AuditTrailInterface
{
    public function record(AuditEntry $entry): void;
}
