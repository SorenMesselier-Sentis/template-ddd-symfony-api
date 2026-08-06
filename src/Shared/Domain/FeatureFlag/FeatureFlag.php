<?php

declare(strict_types=1);

namespace App\Shared\Domain\FeatureFlag;

final readonly class FeatureFlag
{
    public function __construct(
        public string $key,
        public bool $enabled,
        public ?string $description,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
