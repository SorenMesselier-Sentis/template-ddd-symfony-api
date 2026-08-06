<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class FeatureDisabledException extends DomainException
{
    public static function create(string $key): self
    {
        return new self(sprintf('Feature "%s" is currently disabled.', $key));
    }

    public function errorCode(): string
    {
        return 'feature_flag.disabled';
    }
}
