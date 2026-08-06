<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Exception\FeatureDisabledException;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use App\Shared\Domain\FeatureFlag\FeatureGatedMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class FeatureFlagMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly FeatureFlagRepositoryInterface $featureFlags,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof FeatureGatedMessage && !$this->featureFlags->isEnabled($message->requiredFeatureFlag())) {
            throw FeatureDisabledException::create($message->requiredFeatureFlag());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
