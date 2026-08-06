<?php

declare(strict_types=1);

namespace App\User\Application\Query\ExportUserData;

use App\Shared\Domain\Bus\Query\Response;

final class ExportUserDataResponse implements Response
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly array $data,
        public readonly string $exportedAt,
    ) {
    }
}
