<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetMyConsents;

use App\Shared\Domain\Bus\Query\Response;
use App\User\Domain\Entity\Consent;

final class ConsentResponse implements Response
{
    public readonly string $type;
    public readonly string $version;
    public readonly string $givenAt;
    public readonly ?string $withdrawnAt;
    public readonly bool $active;

    public function __construct(Consent $consent)
    {
        $this->type = $consent->type()->value;
        $this->version = $consent->version();
        $this->givenAt = $consent->givenAt()->format(\DateTimeInterface::ATOM);
        $this->withdrawnAt = $consent->withdrawnAt()?->format(\DateTimeInterface::ATOM);
        $this->active = $consent->isActive();
    }
}
