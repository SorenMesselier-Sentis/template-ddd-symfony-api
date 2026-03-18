<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

use App\Shared\Domain\BUS\Query\Query;

interface QueryBusInterface
{
    public function ask(Query $query): Response;
}
