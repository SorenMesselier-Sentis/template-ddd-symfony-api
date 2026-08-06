<?php

declare(strict_types=1);

namespace App\Shared\Domain\FeatureFlag;

/**
 * Implemented by a command/query whose execution is conditional on a feature
 * flag — declared on the message, the same way authorization and auditing
 * are (see AuthorizedMessageContract, AuditableMessage). FeatureFlagMessageMiddleware
 * rejects the message with FeatureDisabledException while the flag is off.
 */
interface FeatureGatedMessage
{
    public function requiredFeatureFlag(): string;
}
