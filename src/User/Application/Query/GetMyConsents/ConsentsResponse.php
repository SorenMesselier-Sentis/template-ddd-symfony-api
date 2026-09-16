<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetMyConsents;

use App\Shared\Domain\Bus\Query\Response;

final class ConsentsResponse implements Response
{
    /**
     * @param list<ConsentResponse> $consents
     */
    public function __construct(
        public readonly array $consents,
    ) {
    }
}
