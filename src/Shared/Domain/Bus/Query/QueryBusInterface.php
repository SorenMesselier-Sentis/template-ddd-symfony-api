<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

interface QueryBusInterface
{
    /**
     * @template TResponse
     *
     * @param Query<TResponse> $query
     *
     * @return TResponse
     */
    public function ask(Query $query): mixed;
}
